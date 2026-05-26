<?php

namespace App\Services;

use App\Exceptions\AttendanceSessionException;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class AttendanceSessionService
{
    private const SESSION_MINUTES = 90;

    private const LATE_AFTER_MINUTES = 10;

    /**
     * @return array<string, mixed>
     */
    public function professorOverview(User $user): array
    {
        $professorId = $this->professorIdForUser($user);

        if ($professorId === null) {
            return [
                'metrics' => [
                    'recordedSessions' => 0,
                    'present' => 0,
                    'absent' => 0,
                    'late' => 0,
                ],
                'sessionRecords' => [],
                'courseAttendance' => [],
                'activeSession' => null,
            ];
        }

        $activeSession = $this->activeSessionForProfessor($professorId);

        return [
            'metrics' => $this->professorMetrics($professorId),
            'sessionRecords' => $this->professorSessionRecords($professorId),
            'courseAttendance' => $this->courseAttendance($professorId),
            'activeSession' => $activeSession === null ? null : $this->sessionPayload($activeSession->id),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function availableClasses(User $user): array
    {
        $professorId = $this->professorIdForUser($user);

        if ($professorId === null) {
            return [];
        }

        return $this->activeClassRows($professorId)
            ->reject(fn (object $class): bool => $this->duplicateActiveSessionExists($class))
            ->map(fn (object $class): array => $this->availableClassPayload($class))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function startSession(User $user, string $courseIdentifier, int $courseScheduleId): array
    {
        $professorId = $this->professorIdForUser($user);

        if ($professorId === null) {
            throw new AttendanceSessionException('Professor profile was not found.', 404);
        }

        $class = $this->activeClassRows($professorId)
            ->first(function (object $class) use ($courseIdentifier, $courseScheduleId): bool {
                return (int) $class->schedule_id === $courseScheduleId
                    && in_array($courseIdentifier, [
                        (string) $class->course_id,
                        (string) $class->course_key,
                        (string) $class->course_code,
                    ], true);
            });

        if ($class === null) {
            throw new AttendanceSessionException('This class is not active right now.', 422);
        }

        $existing = $this->duplicateActiveSession($class);

        if ($existing !== null) {
            return $this->sessionPayload((int) $existing->id);
        }

        $sessionId = DB::transaction(function () use ($class, $professorId): int {
            $duplicate = $this->duplicateActiveSession($class);

            if ($duplicate !== null) {
                return (int) $duplicate->id;
            }

            $now = now();
            $sessionId = DB::table('attendance_sessions')->insertGetId([
                'course_id' => (int) $class->course_id,
                'professor_id' => $professorId,
                'course_schedule_id' => (int) $class->schedule_id,
                'code' => $this->generateCode(),
                'qr_token' => $this->generateQrToken(),
                'starts_at' => $now,
                'ends_at' => $now->copy()->addMinutes(self::SESSION_MINUTES),
                'late_after_at' => $now->copy()->addMinutes(self::LATE_AFTER_MINUTES),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $studentIds = DB::table('student_enrollments')
                ->where('course_id', (int) $class->course_id)
                ->whereIn('status', ['active', 'registered', 'upcoming'])
                ->orderBy('student_id')
                ->pluck('student_id');

            foreach ($studentIds as $studentId) {
                DB::table('attendance_session_records')->insert([
                    'attendance_session_id' => $sessionId,
                    'student_id' => (int) $studentId,
                    'status' => 'pending',
                    'checked_in_at' => null,
                    'method' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            return (int) $sessionId;
        });

        return $this->sessionPayload($sessionId);
    }

    /**
     * @return array<string, mixed>
     */
    public function sessionForProfessor(User $user, int $sessionId): array
    {
        $professorId = $this->professorIdForUser($user);

        if ($professorId === null) {
            throw new AttendanceSessionException('Professor profile was not found.', 404);
        }

        $session = DB::table('attendance_sessions')
            ->where('id', $sessionId)
            ->where('professor_id', $professorId)
            ->first();

        if ($session === null) {
            throw new AttendanceSessionException('Attendance session was not found.', 404);
        }

        return $this->sessionPayload($sessionId);
    }

    /**
     * @return array<string, mixed>
     */
    public function closeSession(User $user, int $sessionId): array
    {
        $professorId = $this->professorIdForUser($user);

        if ($professorId === null) {
            throw new AttendanceSessionException('Professor profile was not found.', 404);
        }

        $updated = DB::table('attendance_sessions')
            ->where('id', $sessionId)
            ->where('professor_id', $professorId)
            ->whereNull('closed_at')
            ->update([
                'closed_at' => now(),
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            throw new AttendanceSessionException('Attendance session was not found or already closed.', 404);
        }

        return $this->sessionPayload($sessionId);
    }

    /**
     * @return array<string, mixed>
     */
    public function checkIn(User $user, ?string $code, ?string $qrToken): array
    {
        $student = DB::table('students')->where('user_id', $user->id)->first();

        if ($student === null) {
            throw new AttendanceSessionException('Student profile was not found.', 404);
        }

        $session = $this->resolveCheckInSession($code, $qrToken);
        $now = now();

        $enrollment = DB::table('student_enrollments')
            ->where('student_id', (int) $student->id)
            ->where('course_id', (int) $session->course_id)
            ->where('status', '!=', 'dropped')
            ->first();

        if ($enrollment === null) {
            throw new AttendanceSessionException('You are not enrolled in this course.', 403);
        }

        $status = $now->lte(Carbon::parse($session->late_after_at)) ? 'present' : 'late';
        $method = $qrToken !== null && trim($qrToken) !== '' ? 'qr' : 'code';

        DB::transaction(function () use ($session, $student, $status, $method, $now): void {
            $record = DB::table('attendance_session_records')
                ->where('attendance_session_id', (int) $session->id)
                ->where('student_id', (int) $student->id)
                ->lockForUpdate()
                ->first();

            if ($record === null) {
                $recordId = DB::table('attendance_session_records')->insertGetId([
                    'attendance_session_id' => (int) $session->id,
                    'student_id' => (int) $student->id,
                    'status' => 'pending',
                    'checked_in_at' => null,
                    'method' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $record = DB::table('attendance_session_records')->where('id', $recordId)->first();
            }

            if ($record === null || $record->checked_in_at !== null || $record->status !== 'pending') {
                throw new AttendanceSessionException('You have already checked in for this session.', 409);
            }

            DB::table('attendance_session_records')
                ->where('id', (int) $record->id)
                ->update([
                    'status' => $status,
                    'checked_in_at' => $now,
                    'method' => $method,
                    'updated_at' => $now,
                ]);
        });

        $payload = $this->sessionPayload((int) $session->id);

        return [
            'status' => $status,
            'statusLabel' => ucfirst($status),
            'checkedInAt' => $now->toISOString(),
            'course' => [
                'courseId' => $payload['courseId'],
                'code' => $payload['courseCode'],
                'name' => $payload['courseName'],
                'room' => $payload['room'],
            ],
            'session' => [
                'id' => $payload['id'],
                'startsAt' => $payload['startsAt'],
                'endsAt' => $payload['endsAt'],
                'lateAfterAt' => $payload['lateAfterAt'],
            ],
            'professor' => $payload['professor'],
        ];
    }

    private function professorIdForUser(User $user): ?int
    {
        $professorId = DB::table('professors')->where('user_id', $user->id)->value('id');

        return $professorId === null ? null : (int) $professorId;
    }

    private function activeSessionForProfessor(int $professorId): ?object
    {
        $now = now();

        return DB::table('attendance_sessions')
            ->where('professor_id', $professorId)
            ->whereNull('closed_at')
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>', $now)
            ->orderByDesc('starts_at')
            ->first();
    }

    /**
     * @return Collection<int, object>
     */
    private function activeClassRows(int $professorId): Collection
    {
        $now = now();

        return DB::table('course_professor')
            ->join('courses', 'courses.id', '=', 'course_professor.course_id')
            ->join('course_schedules', 'course_schedules.course_id', '=', 'courses.id')
            ->leftJoin('semesters', 'semesters.id', '=', 'courses.semester_id')
            ->where('course_professor.professor_id', $professorId)
            ->where('courses.status', 'Active')
            ->where(function ($query): void {
                $query->whereNull('courses.semester_id')
                    ->orWhere('semesters.is_current', true);
            })
            ->select(
                'courses.id as course_id',
                'courses.course_key',
                'courses.code as course_code',
                'courses.name as course_name',
                'courses.room as course_room',
                'course_schedules.id as schedule_id',
                'course_schedules.days_label',
                'course_schedules.days',
                'course_schedules.time_label',
                'course_schedules.starts_at',
                'course_schedules.ends_at',
                'course_schedules.room',
                'course_schedules.label',
            )
            ->get()
            ->filter(fn (object $class): bool => $this->scheduleIsActive($class, $now))
            ->sortBy([['starts_at', 'asc'], ['course_code', 'asc']])
            ->values();
    }

    private function scheduleIsActive(object $class, Carbon $now): bool
    {
        if (! in_array($now->format('l'), $this->jsonArray($class->days), true)) {
            return false;
        }

        $start = $this->timeString($class->starts_at);
        $end = $this->timeString($class->ends_at);

        if ($start === '' || $end === '') {
            return false;
        }

        $current = $now->format('H:i:s');

        if ($start <= $end) {
            return $current >= $start && $current <= $end;
        }

        return $current >= $start || $current <= $end;
    }

    private function duplicateActiveSessionExists(object $class): bool
    {
        return $this->duplicateActiveSession($class) !== null;
    }

    private function duplicateActiveSession(object $class): ?object
    {
        return DB::table('attendance_sessions')
            ->where('course_id', (int) $class->course_id)
            ->where('course_schedule_id', (int) $class->schedule_id)
            ->whereNull('closed_at')
            ->where('ends_at', '>', now())
            ->orderByDesc('starts_at')
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function availableClassPayload(object $class): array
    {
        return [
            'courseId' => (string) $class->course_key,
            'courseCode' => (string) $class->course_code,
            'courseName' => (string) $class->course_name,
            'courseScheduleId' => (int) $class->schedule_id,
            'scheduleLabel' => (string) ($class->label ?: 'Lecture'),
            'days' => (string) ($class->days_label ?? ''),
            'time' => (string) ($class->time_label ?: $this->timeRange($class->starts_at, $class->ends_at)),
            'room' => (string) ($class->room ?: $class->course_room ?: ''),
            'enrolledCount' => $this->enrolledCount((int) $class->course_id),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function professorMetrics(int $professorId): array
    {
        $courseIds = $this->professorCourseIds($professorId);

        if ($courseIds === []) {
            return [
                'recordedSessions' => 0,
                'present' => 0,
                'absent' => 0,
                'late' => 0,
            ];
        }

        $legacyRecordedSessions = DB::table('course_attendance_records')
            ->join('student_enrollments', 'student_enrollments.id', '=', 'course_attendance_records.student_enrollment_id')
            ->whereIn('student_enrollments.course_id', $courseIds)
            ->whereIn('course_attendance_records.status', ['present', 'absent', 'late', 'recorded'])
            ->select('student_enrollments.course_id', 'course_attendance_records.held_on', 'course_attendance_records.record_key')
            ->distinct()
            ->get()
            ->count();

        $legacy = DB::table('course_attendance_records')
            ->join('student_enrollments', 'student_enrollments.id', '=', 'course_attendance_records.student_enrollment_id')
            ->whereIn('student_enrollments.course_id', $courseIds)
            ->whereIn('course_attendance_records.status', ['present', 'absent', 'late', 'recorded'])
            ->selectRaw("SUM(CASE WHEN course_attendance_records.status in ('present', 'recorded') THEN 1 ELSE 0 END) as present")
            ->selectRaw("SUM(CASE WHEN course_attendance_records.status = 'absent' THEN 1 ELSE 0 END) as absent")
            ->selectRaw("SUM(CASE WHEN course_attendance_records.status = 'late' THEN 1 ELSE 0 END) as late")
            ->first();

        $sessions = DB::table('attendance_sessions')
            ->join('attendance_session_records', 'attendance_session_records.attendance_session_id', '=', 'attendance_sessions.id')
            ->where('attendance_sessions.professor_id', $professorId)
            ->selectRaw('COUNT(DISTINCT attendance_sessions.id) as recorded_sessions')
            ->selectRaw("SUM(CASE WHEN attendance_session_records.status = 'present' THEN 1 ELSE 0 END) as present")
            ->selectRaw("SUM(CASE WHEN attendance_session_records.status = 'late' THEN 1 ELSE 0 END) as late")
            ->selectRaw("SUM(CASE WHEN attendance_session_records.status = 'absent' THEN 1 ELSE 0 END) as absent")
            ->first();

        return [
            'recordedSessions' => (int) $legacyRecordedSessions + (int) ($sessions->recorded_sessions ?? 0),
            'present' => (int) ($legacy->present ?? 0) + (int) ($sessions->present ?? 0),
            'absent' => (int) ($legacy->absent ?? 0) + (int) ($sessions->absent ?? 0),
            'late' => (int) ($legacy->late ?? 0) + (int) ($sessions->late ?? 0),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function professorSessionRecords(int $professorId): array
    {
        $courseIds = $this->professorCourseIds($professorId);

        if ($courseIds === []) {
            return [];
        }

        $legacy = DB::table('course_attendance_records')
            ->join('student_enrollments', 'student_enrollments.id', '=', 'course_attendance_records.student_enrollment_id')
            ->join('courses', 'courses.id', '=', 'student_enrollments.course_id')
            ->whereIn('student_enrollments.course_id', $courseIds)
            ->whereIn('course_attendance_records.status', ['present', 'absent', 'late', 'recorded'])
            ->groupBy('student_enrollments.course_id', 'courses.course_key', 'courses.code', 'courses.name', 'courses.room', 'course_attendance_records.held_on', 'course_attendance_records.type')
            ->orderByDesc('course_attendance_records.held_on')
            ->limit(20)
            ->select(
                'courses.course_key',
                'courses.code as course_code',
                'courses.name as course_name',
                'courses.room',
                'course_attendance_records.held_on',
                'course_attendance_records.type',
            )
            ->selectRaw("SUM(CASE WHEN course_attendance_records.status in ('present', 'recorded') THEN 1 ELSE 0 END) as present")
            ->selectRaw("SUM(CASE WHEN course_attendance_records.status = 'absent' THEN 1 ELSE 0 END) as absent")
            ->selectRaw("SUM(CASE WHEN course_attendance_records.status = 'late' THEN 1 ELSE 0 END) as late")
            ->get()
            ->map(fn (object $record): array => [
                'id' => 'legacy-'.$record->course_key.'-'.$record->held_on,
                'courseId' => (string) $record->course_key,
                'courseCode' => (string) $record->course_code,
                'courseName' => (string) $record->course_name,
                'date' => $this->dateLabel($record->held_on),
                'time' => '',
                'room' => (string) ($record->room ?? ''),
                'present' => (int) $record->present,
                'absent' => (int) $record->absent,
                'late' => (int) $record->late,
                'status' => 'Recorded',
            ]);

        $sessions = DB::table('attendance_sessions')
            ->join('courses', 'courses.id', '=', 'attendance_sessions.course_id')
            ->leftJoin('course_schedules', 'course_schedules.id', '=', 'attendance_sessions.course_schedule_id')
            ->leftJoin('attendance_session_records', 'attendance_session_records.attendance_session_id', '=', 'attendance_sessions.id')
            ->where('attendance_sessions.professor_id', $professorId)
            ->groupBy(
                'attendance_sessions.id',
                'attendance_sessions.starts_at',
                'attendance_sessions.ends_at',
                'attendance_sessions.closed_at',
                'courses.course_key',
                'courses.code',
                'courses.name',
                'courses.room',
                'course_schedules.room',
                'course_schedules.time_label',
            )
            ->orderByDesc('attendance_sessions.starts_at')
            ->select(
                'attendance_sessions.id',
                'attendance_sessions.starts_at',
                'attendance_sessions.ends_at',
                'attendance_sessions.closed_at',
                'courses.course_key',
                'courses.code as course_code',
                'courses.name as course_name',
                'courses.room as course_room',
                'course_schedules.room as schedule_room',
                'course_schedules.time_label',
            )
            ->selectRaw("SUM(CASE WHEN attendance_session_records.status = 'present' THEN 1 ELSE 0 END) as present")
            ->selectRaw("SUM(CASE WHEN attendance_session_records.status = 'absent' THEN 1 ELSE 0 END) as absent")
            ->selectRaw("SUM(CASE WHEN attendance_session_records.status = 'late' THEN 1 ELSE 0 END) as late")
            ->limit(20)
            ->get()
            ->map(fn (object $record): array => [
                'id' => 'session-'.$record->id,
                'courseId' => (string) $record->course_key,
                'courseCode' => (string) $record->course_code,
                'courseName' => (string) $record->course_name,
                'date' => $this->dateLabel($record->starts_at),
                'time' => (string) ($record->time_label ?: $this->timeRange($record->starts_at, $record->ends_at)),
                'room' => (string) ($record->schedule_room ?: $record->course_room ?: ''),
                'present' => (int) $record->present,
                'absent' => (int) $record->absent,
                'late' => (int) $record->late,
                'status' => $this->sessionRecordStatus($record),
            ]);

        return $sessions
            ->concat($legacy)
            ->take(20)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function courseAttendance(int $professorId): array
    {
        return DB::table('course_professor')
            ->join('courses', 'courses.id', '=', 'course_professor.course_id')
            ->leftJoin('student_enrollments', 'student_enrollments.course_id', '=', 'courses.id')
            ->leftJoin('course_attendance_records', 'course_attendance_records.student_enrollment_id', '=', 'student_enrollments.id')
            ->where('course_professor.professor_id', $professorId)
            ->groupBy('courses.id', 'courses.course_key', 'courses.code', 'courses.name')
            ->orderBy('courses.code')
            ->select('courses.id', 'courses.course_key', 'courses.code', 'courses.name')
            ->selectRaw('COUNT(course_attendance_records.id) as total_records')
            ->selectRaw("SUM(CASE WHEN course_attendance_records.status in ('present', 'late', 'recorded') THEN 1 ELSE 0 END) as attended_records")
            ->get()
            ->map(fn (object $course): array => [
                'courseId' => (string) $course->course_key,
                'code' => (string) $course->code,
                'name' => (string) $course->name,
                'attendanceRate' => (int) ((int) $course->total_records > 0 ? round(((int) $course->attended_records / (int) $course->total_records) * 100) : 0),
            ])
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function professorCourseIds(int $professorId): array
    {
        return DB::table('course_professor')
            ->where('professor_id', $professorId)
            ->pluck('course_id')
            ->map(fn (mixed $courseId): int => (int) $courseId)
            ->all();
    }

    private function enrolledCount(int $courseId): int
    {
        return DB::table('student_enrollments')
            ->where('course_id', $courseId)
            ->whereIn('status', ['active', 'registered', 'upcoming'])
            ->count();
    }

    private function resolveCheckInSession(?string $code, ?string $qrToken): object
    {
        $code = trim((string) $code);
        $qrToken = trim((string) $qrToken);

        $query = DB::table('attendance_sessions');

        if ($qrToken !== '') {
            $query->where('qr_token', $qrToken);
        } elseif ($code !== '') {
            $query->where('code', $code);
        } else {
            throw new AttendanceSessionException('Enter a 6-digit code or scan a QR token.', 422);
        }

        $now = now();
        $active = (clone $query)
            ->whereNull('closed_at')
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>', $now)
            ->orderByDesc('starts_at')
            ->first();

        if ($active !== null) {
            return $active;
        }

        $session = $query->orderByDesc('starts_at')->first();

        if ($session === null) {
            throw new AttendanceSessionException('Invalid attendance code or QR token.', 404);
        }

        if ($session->closed_at !== null || Carbon::parse($session->ends_at)->lte($now)) {
            throw new AttendanceSessionException('Attendance session has expired.', 422);
        }

        throw new AttendanceSessionException('Attendance session is not active yet.', 422);
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionPayload(int $sessionId): array
    {
        $session = DB::table('attendance_sessions')
            ->join('courses', 'courses.id', '=', 'attendance_sessions.course_id')
            ->join('professors', 'professors.id', '=', 'attendance_sessions.professor_id')
            ->join('users as professor_users', 'professor_users.id', '=', 'professors.user_id')
            ->leftJoin('course_schedules', 'course_schedules.id', '=', 'attendance_sessions.course_schedule_id')
            ->where('attendance_sessions.id', $sessionId)
            ->select(
                'attendance_sessions.*',
                'courses.course_key',
                'courses.code as course_code',
                'courses.name as course_name',
                'courses.room as course_room',
                'course_schedules.days_label',
                'course_schedules.time_label',
                'course_schedules.room as schedule_room',
                'course_schedules.label as schedule_label',
                'professor_users.public_id as professor_public_id',
                'professor_users.name as professor_name',
                'professor_users.email as professor_email',
            )
            ->first();

        if ($session === null) {
            throw new AttendanceSessionException('Attendance session was not found.', 404);
        }

        $records = $this->sessionRecords((int) $session->id);
        $startsAt = Carbon::parse($session->starts_at);
        $endsAt = Carbon::parse($session->ends_at);
        $lateAfterAt = Carbon::parse($session->late_after_at);
        $closedAt = $session->closed_at === null ? null : Carbon::parse($session->closed_at);
        $now = now();
        $isActive = $closedAt === null && $startsAt->lte($now) && $endsAt->gt($now);
        $checkedInCount = $records->whereIn('status', ['present', 'late'])->count();

        return [
            'id' => (string) $session->id,
            'courseId' => (string) $session->course_key,
            'courseCode' => (string) $session->course_code,
            'courseName' => (string) $session->course_name,
            'room' => (string) ($session->schedule_room ?: $session->course_room ?: ''),
            'schedule' => [
                'label' => (string) ($session->schedule_label ?: 'Lecture'),
                'days' => (string) ($session->days_label ?? ''),
                'time' => (string) ($session->time_label ?: $this->timeRange($session->starts_at, $session->ends_at)),
            ],
            'professor' => [
                'id' => (string) $session->professor_public_id,
                'name' => (string) $session->professor_name,
                'email' => (string) $session->professor_email,
            ],
            'startsAt' => $startsAt->toISOString(),
            'endsAt' => $endsAt->toISOString(),
            'lateAfterAt' => $lateAfterAt->toISOString(),
            'closedAt' => $closedAt?->toISOString(),
            'isActive' => $isActive,
            'remainingSeconds' => $isActive ? max(0, $endsAt->getTimestamp() - $now->getTimestamp()) : 0,
            'checkInCode' => (string) $session->code,
            'qrToken' => (string) $session->qr_token,
            'qrPayload' => (string) $session->qr_token,
            'totalEnrolled' => $records->count(),
            'checkedInCount' => $checkedInCount,
            'presentCount' => $records->where('status', 'present')->count(),
            'lateCount' => $records->where('status', 'late')->count(),
            'pendingCount' => $records->where('status', 'pending')->count(),
            'records' => $records->values()->all(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function sessionRecords(int $sessionId): Collection
    {
        return DB::table('attendance_session_records')
            ->join('students', 'students.id', '=', 'attendance_session_records.student_id')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->where('attendance_session_records.attendance_session_id', $sessionId)
            ->orderBy('users.name')
            ->select(
                'attendance_session_records.*',
                'students.student_number',
                'users.public_id',
                'users.institution_id',
                'users.name',
                'users.email',
            )
            ->get()
            ->map(fn (object $record): array => [
                'id' => (string) $record->id,
                'studentId' => (string) $record->public_id,
                'studentNumber' => (string) $record->student_number,
                'institutionId' => (string) $record->institution_id,
                'name' => (string) $record->name,
                'email' => (string) $record->email,
                'status' => (string) $record->status,
                'statusLabel' => ucfirst((string) $record->status),
                'checkedInAt' => $record->checked_in_at === null ? null : Carbon::parse($record->checked_in_at)->toISOString(),
                'method' => $record->method === null ? null : (string) $record->method,
            ]);
    }

    private function generateCode(): string
    {
        do {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (
            DB::table('attendance_sessions')
                ->where('code', $code)
                ->exists()
        );

        return $code;
    }

    private function generateQrToken(): string
    {
        do {
            $token = bin2hex(random_bytes(24));
        } while (
            DB::table('attendance_sessions')
                ->where('qr_token', $token)
                ->exists()
        );

        return $token;
    }

    private function sessionRecordStatus(object $record): string
    {
        $now = now();

        if ($record->closed_at !== null || Carbon::parse($record->ends_at)->lte($now)) {
            return 'Recorded';
        }

        return 'Open';
    }

    private function dateLabel(?string $date): string
    {
        return $date === null || $date === '' ? '' : Carbon::parse($date)->format('M j, Y');
    }

    private function timeRange(?string $startsAt, ?string $endsAt): string
    {
        return trim($this->timeValue($startsAt).' - '.$this->timeValue($endsAt), ' -');
    }

    private function timeValue(?string $time): string
    {
        if ($time === null || $time === '') {
            return '';
        }

        try {
            return Carbon::parse($time)->format('H:i');
        } catch (\Throwable) {
            return substr($time, 0, 5);
        }
    }

    private function timeString(mixed $time): string
    {
        if ($time === null || $time === '') {
            return '';
        }

        if ($time instanceof \DateTimeInterface) {
            return Carbon::instance($time)->format('H:i:s');
        }

        $value = (string) $time;

        if (preg_match('/^\d{2}:\d{2}$/', $value) === 1) {
            return $value.':00';
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value) === 1) {
            return $value;
        }

        try {
            return Carbon::parse($value)->format('H:i:s');
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * @return array<int, string>
     */
    private function jsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, 'is_string'));
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_string')) : [];
    }
}
