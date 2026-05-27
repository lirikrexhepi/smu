<?php

namespace App\Services;

use App\Models\Identity\Student;
use App\Models\Gradebook\StudentEnrollment;
use App\Models\Attendance\CourseAttendanceRecord;
use App\Models\Attendance\AttendanceHistoryRecord;
use App\Models\Identity\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class StudentAttendanceService
{
    public function __construct(private readonly StudentAcademicRecordsService $records) {}

    /**
     * @return array<string, mixed>
     */
    public function forRequest(Request $request): array
    {
        $user = $request->user();
        $student = Student::where('user_id', $user?->id)->first();

        if ($student === null) {
            return $this->emptyResponse($user);
        }

        $semester = $this->resolveSemester((int) $student->id, (string) $request->query('semester', ''));
        $course = $this->resolveCourse((int) $student->id, (string) $request->query('courseId', ''), $semester?->id);
        $week = $this->resolveWeek((string) $request->query('week', ''));

        return [
            'studentKey' => (string) ($user?->public_id ?? $student->student_key ?? ''),
            'semester' => (string) ($semester?->name ?? ''),
            'academicYear' => (string) ($semester?->academic_year_name ?? ''),
            'selectedCourseId' => $course === null ? null : (string) $course->course_key,
            'selectedSemester' => (string) ($semester?->name ?? ''),
            'selectedWeek' => (string) ($request->query('week', '') ?: $week->starts_on),
            'filters' => [
                'courses' => $this->courseOptions((int) $student->id, $semester?->id),
                'semesters' => $this->semesterOptions((int) $student->id),
            ],
            'week' => $this->weekPayload($week, (string) $request->query('week', '')),
            'summary' => $this->summary((int) $student->id, $course?->id, $semester?->id),
            'lastRecorded' => $this->lastRecorded((int) $student->id, $course?->id, $semester?->id),
            'weeklySchedule' => $this->weeklySchedule((int) $student->id, $week, $course?->id, $semester?->id),
            'history' => $this->history((int) $student->id, $course?->id, $semester?->id),
        ];
    }

    private function resolveSemester(int $studentId, string $requested): ?object
    {
        $requested = trim($requested);

        $query = StudentEnrollment::join('semesters', 'semesters.id', '=', 'student_enrollments.semester_id')
            ->leftJoin('academic_years', 'academic_years.id', '=', 'semesters.academic_year_id')
            ->where('student_enrollments.student_id', $studentId)
            ->where('student_enrollments.status', '!=', 'dropped')
            ->select('semesters.*', 'academic_years.name as academic_year_name')
            ->distinct();

        if ($requested !== '' && $requested !== 'all') {
            $semester = (clone $query)
                ->where(function ($query) use ($requested): void {
                    $query->where('semesters.name', $requested)
                        ->orWhere('semesters.code', $requested);
                })
                ->first();

            if ($semester !== null) {
                return $semester;
            }
        }

        return $query
            ->orderByDesc('semesters.is_current')
            ->orderByDesc('semesters.number')
            ->orderByDesc('semesters.id')
            ->first();
    }

    private function resolveCourse(int $studentId, string $requested, ?int $semesterId): ?object
    {
        $requested = trim($requested);

        if ($requested === '' || $requested === 'all') {
            return null;
        }

        return StudentEnrollment::join('courses', 'courses.id', '=', 'student_enrollments.course_id')
            ->where('student_enrollments.student_id', $studentId)
            ->where('student_enrollments.status', '!=', 'dropped')
            ->when($semesterId !== null, fn ($query) => $query->where('student_enrollments.semester_id', $semesterId))
            ->where(function ($query) use ($requested): void {
                $query->where('courses.course_key', $requested)
                    ->orWhere('courses.code', $requested);

                if (ctype_digit($requested)) {
                    $query->orWhere('courses.id', (int) $requested);
                }
            })
            ->select('courses.*')
            ->first();
    }

    private function resolveWeek(string $requested): object
    {
        $date = $this->requestedWeekDate($requested) ?? Carbon::today();
        $start = $date->copy()->startOfWeek(Carbon::MONDAY);

        return (object) [
            'starts_on' => $start->toDateString(),
            'ends_on' => $start->copy()->addDays(4)->toDateString(),
            'label' => $this->weekLabel($start->toDateString(), $start->copy()->addDays(4)->toDateString()),
        ];
    }

    private function requestedWeekDate(string $requested): ?Carbon
    {
        $requested = trim($requested);

        if ($requested === '') {
            return null;
        }

        try {
            return Carbon::parse($requested)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<int, string>
     */
    private function semesterOptions(int $studentId): array
    {
        return StudentEnrollment::join('semesters', 'semesters.id', '=', 'student_enrollments.semester_id')
            ->where('student_enrollments.student_id', $studentId)
            ->where('student_enrollments.status', '!=', 'dropped')
            ->select('semesters.name', 'semesters.is_current', 'semesters.number', 'semesters.id')
            ->distinct()
            ->orderByDesc('semesters.is_current')
            ->orderByDesc('semesters.number')
            ->orderByDesc('semesters.id')
            ->pluck('semesters.name')
            ->map(fn (mixed $name): string => (string) $name)
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function courseOptions(int $studentId, ?int $semesterId): array
    {
        return StudentEnrollment::join('courses', 'courses.id', '=', 'student_enrollments.course_id')
            ->where('student_enrollments.student_id', $studentId)
            ->where('student_enrollments.status', '!=', 'dropped')
            ->when($semesterId !== null, fn ($query) => $query->where('student_enrollments.semester_id', $semesterId))
            ->orderBy('courses.code')
            ->select('courses.course_key', 'courses.code', 'courses.name')
            ->get()
            ->map(fn (object $course): array => [
                'courseId' => (string) $course->course_key,
                'code' => (string) $course->code,
                'name' => (string) $course->name,
                'label' => trim((string) $course->code.' - '.(string) $course->name),
            ])
            ->all();
    }

    /**
     * @return array<string, string|null>
     */
    private function weekPayload(object $week, string $requested): array
    {
        return [
            'startDate' => (string) $week->starts_on,
            'endDate' => (string) $week->ends_on,
            'label' => (string) $week->label,
            'requestedDate' => $requested !== '' ? $requested : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(int $studentId, ?int $courseId, ?int $semesterId): array
    {
        $summary = $this->attendanceAggregate($studentId, $courseId, $semesterId);

        if ($summary === null || (int) $summary->total_sessions === 0) {
            return $this->emptySummary();
        }

        $totalSessions = (int) $summary->total_sessions;
        $absences = (int) $summary->absences;
        $lateRecords = (int) $summary->late_records;

        return [
            'overallAttendance' => (int) $summary->attendance_percentage,
            'presentSessions' => (int) $summary->sessions_attended,
            'totalSessions' => $totalSessions,
            'absences' => $absences,
            'lateRecords' => $lateRecords,
            'absenceRate' => $totalSessions > 0 ? (int) round(($absences / $totalSessions) * 100) : 0,
            'lateRate' => $totalSessions > 0 ? (int) round(($lateRecords / $totalSessions) * 100) : 0,
            'comparisonVsLast4Weeks' => [
                'value' => 0,
                'direction' => 'flat',
                'label' => 'Calculated from attendance records',
            ],
        ];
    }

    private function attendanceAggregate(int $studentId, ?int $courseId, ?int $semesterId): ?object
    {
        return CourseAttendanceRecord::join('student_enrollments', 'student_enrollments.id', '=', 'course_attendance_records.student_enrollment_id')
            ->join('courses', 'courses.id', '=', 'student_enrollments.course_id')
            ->where('student_enrollments.student_id', $studentId)
            ->where('student_enrollments.status', '!=', 'dropped')
            ->whereIn('course_attendance_records.status', ['present', 'absent', 'late', 'recorded'])
            ->when($courseId !== null, fn ($query) => $query->where('student_enrollments.course_id', $courseId))
            ->when($semesterId !== null, fn ($query) => $query->where('student_enrollments.semester_id', $semesterId))
            ->selectRaw('COUNT(*) as total_sessions')
            ->selectRaw("SUM(CASE WHEN course_attendance_records.status in ('present', 'late', 'recorded') THEN 1 ELSE 0 END) as sessions_attended")
            ->selectRaw("SUM(CASE WHEN course_attendance_records.status = 'absent' THEN 1 ELSE 0 END) as absences")
            ->selectRaw("SUM(CASE WHEN course_attendance_records.status = 'late' THEN 1 ELSE 0 END) as late_records")
            ->selectRaw("ROUND((SUM(CASE WHEN course_attendance_records.status in ('present', 'late', 'recorded') THEN 1 ELSE 0 END) * 100.0) / NULLIF(COUNT(*), 0)) as attendance_percentage")
            ->first();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function lastRecorded(int $studentId, ?int $courseId, ?int $semesterId): ?array
    {
        $record = CourseAttendanceRecord::join('student_enrollments', 'student_enrollments.id', '=', 'course_attendance_records.student_enrollment_id')
            ->join('courses', 'courses.id', '=', 'student_enrollments.course_id')
            ->where('student_enrollments.student_id', $studentId)
            ->whereIn('course_attendance_records.status', ['present', 'absent', 'late', 'recorded'])
            ->when($courseId !== null, fn ($query) => $query->where('student_enrollments.course_id', $courseId))
            ->when($semesterId !== null, fn ($query) => $query->where('student_enrollments.semester_id', $semesterId))
            ->orderByDesc('course_attendance_records.held_on')
            ->orderByDesc('course_attendance_records.id')
            ->select(
                'course_attendance_records.*',
                'courses.course_key',
                'courses.code as course_code',
                'courses.name as course_name',
            )
            ->first();

        if ($record === null) {
            return null;
        }

        return [
            'courseId' => (string) $record->course_key,
            'courseCode' => (string) $record->course_code,
            'courseName' => (string) $record->course_name,
            'date' => (string) ($record->held_on ?? ''),
            'dateLabel' => (string) ($record->date_label ?: $this->dateLabel($record->held_on)),
            'time' => '',
            'status' => 'recorded',
            'statusLabel' => 'Recorded',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function weeklySchedule(int $studentId, object $week, ?int $courseId, ?int $semesterId): array
    {
        $days = $this->scheduleDays($week);
        $blocks = $this->derivedScheduleBlocks($studentId, $week, $courseId, $semesterId);

        return $days
            ->map(fn (object $day): array => [
                'date' => (string) $day->day_on,
                'dayName' => (string) $day->day_name,
                'dayShort' => (string) $day->day_short,
                'dateLabel' => (string) $day->date_label,
                'isToday' => Carbon::parse($day->day_on)->isSameDay(Carbon::today()),
                'blocks' => $blocks
                    ->where('day_on', (string) $day->day_on)
                    ->sortBy([['starts_at', 'asc'], ['course_code', 'asc']])
                    ->map(fn (object $block): array => $this->scheduleBlockPayload($block))
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    private function scheduleDays(object $week): Collection
    {
        $days = collect();
        $day = Carbon::parse($week->starts_on)->startOfDay();
        $end = Carbon::parse($week->ends_on)->startOfDay();

        while ($day->lte($end)) {
            $days->push((object) [
                'day_on' => $day->toDateString(),
                'day_name' => $day->format('l'),
                'day_short' => $day->format('D'),
                'date_label' => $day->format('M j'),
            ]);
            $day->addDay();
        }

        return $days;
    }

    private function derivedScheduleBlocks(int $studentId, object $week, ?int $courseId, ?int $semesterId): Collection
    {
        $schedules = StudentEnrollment::join('courses', 'courses.id', '=', 'student_enrollments.course_id')
            ->join('course_schedules', 'course_schedules.course_id', '=', 'courses.id')
            ->leftJoin('course_professor', function ($join): void {
                $join->on('course_professor.course_id', '=', 'courses.id')
                    ->where('course_professor.role', '=', 'instructor');
            })
            ->leftJoin('professors', 'professors.id', '=', 'course_professor.professor_id')
            ->leftJoin('users as professor_users', 'professor_users.id', '=', 'professors.user_id')
            ->where('student_enrollments.student_id', $studentId)
            ->whereIn('student_enrollments.status', ['active', 'registered', 'upcoming'])
            ->when($courseId !== null, fn ($query) => $query->where('courses.id', $courseId))
            ->when($semesterId !== null, fn ($query) => $query->where('student_enrollments.semester_id', $semesterId))
            ->select(
                'student_enrollments.id as enrollment_id',
                'courses.course_key',
                'courses.code as course_code',
                'courses.name as course_name',
                'professor_users.name as professor_name',
                'course_schedules.days',
                'course_schedules.time_label',
                'course_schedules.starts_at',
                'course_schedules.ends_at',
                'course_schedules.room',
                'course_schedules.label as type',
            )
            ->get();

        $records = CourseAttendanceRecord::whereIn('student_enrollment_id', $schedules->pluck('enrollment_id')->all())
            ->whereBetween('held_on', [$week->starts_on, $week->ends_on])
            ->get()
            ->keyBy(fn (object $record): string => $record->student_enrollment_id.'|'.$record->held_on);

        $blocks = collect();
        $day = Carbon::parse($week->starts_on)->startOfDay();
        $end = Carbon::parse($week->ends_on)->startOfDay();

        while ($day->lte($end)) {
            $dayName = $day->format('l');

            foreach ($schedules as $schedule) {
                if (! in_array($dayName, $this->jsonArray($schedule->days), true)) {
                    continue;
                }

                $record = $records->get($schedule->enrollment_id.'|'.$day->toDateString());
                $status = (string) ($record?->status ?? 'scheduled');

                $blocks->push((object) [
                    'id' => 'derived-'.$day->toDateString().'-'.$schedule->course_key,
                    'day_on' => $day->toDateString(),
                    'course_key' => $schedule->course_key,
                    'course_code' => $schedule->course_code,
                    'course_name' => $schedule->course_name,
                    'professor_name' => $schedule->professor_name,
                    'time_label' => $schedule->time_label,
                    'starts_at' => $schedule->starts_at,
                    'ends_at' => $schedule->ends_at,
                    'room' => $schedule->room,
                    'type' => $schedule->type ?: 'Lecture',
                    'status' => $status,
                    'status_label' => (string) ($record?->status_label ?: ucfirst($status)),
                    'tone' => $this->attendanceTone($status),
                ]);
            }

            $day->addDay();
        }

        return $blocks;
    }

    /**
     * @return array<string, string>
     */
    private function scheduleBlockPayload(object $block): array
    {
        return [
            'id' => (string) $block->id,
            'courseId' => (string) $block->course_key,
            'courseCode' => (string) $block->course_code,
            'courseName' => (string) $block->course_name,
            'professor' => (string) ($block->professor_name ?? ''),
            'time' => (string) ($block->time_label ?: $this->timeRange($block->starts_at, $block->ends_at)),
            'startTime' => $this->timeValue($block->starts_at),
            'endTime' => $this->timeValue($block->ends_at),
            'room' => (string) ($block->room ?? ''),
            'type' => (string) ($block->type ?? ''),
            'status' => (string) ($block->status ?? 'scheduled'),
            'statusLabel' => (string) ($block->status_label ?: ucfirst((string) ($block->status ?? 'scheduled'))),
            'tone' => $this->tone($block->tone),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function history(int $studentId, ?int $courseId, ?int $semesterId): array
    {
        return AttendanceHistoryRecord::join('courses', 'courses.id', '=', 'attendance_history_records.course_id')
            ->where('attendance_history_records.student_id', $studentId)
            ->when($courseId !== null, fn ($query) => $query->where('attendance_history_records.course_id', $courseId))
            ->when($semesterId !== null, fn ($query) => $query->where('courses.semester_id', $semesterId))
            ->orderByDesc('attendance_history_records.recorded_on')
            ->orderByDesc('attendance_history_records.id')
            ->select(
                'attendance_history_records.*',
                'courses.course_key',
                'courses.code as course_code',
                'courses.name as course_name',
            )
            ->limit(30)
            ->get()
            ->map(fn (object $record): array => [
                'id' => (string) ($record->record_key ?: $record->id),
                'courseId' => (string) $record->course_key,
                'courseCode' => (string) $record->course_code,
                'courseName' => (string) $record->course_name,
                'date' => (string) ($record->date_label ?: $this->dateLabel($record->recorded_on)),
                'dateIso' => $record->recorded_on ? (is_string($record->recorded_on) ? $record->recorded_on : $record->recorded_on->toDateString()) : '',
                'time' => (string) ($record->time_label ?? ''),
                'type' => (string) ($record->type ?? ''),
                'professor' => (string) ($record->professor_name ?? ''),
                'result' => (string) $record->result,
                'resultLabel' => (string) ($record->result_label ?: ucfirst((string) $record->result)),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyResponse(?User $user): array
    {
        return [
            'studentKey' => (string) ($user?->public_id ?? ''),
            'semester' => '',
            'academicYear' => '',
            'selectedCourseId' => null,
            'selectedSemester' => '',
            'selectedWeek' => '',
            'filters' => ['courses' => [], 'semesters' => []],
            'week' => ['startDate' => '', 'endDate' => '', 'label' => '', 'requestedDate' => null],
            'summary' => $this->emptySummary(),
            'lastRecorded' => null,
            'weeklySchedule' => [],
            'history' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptySummary(): array
    {
        return [
            'overallAttendance' => 0,
            'presentSessions' => 0,
            'totalSessions' => 0,
            'absences' => 0,
            'lateRecords' => 0,
            'absenceRate' => 0,
            'lateRate' => 0,
            'comparisonVsLast4Weeks' => [
                'value' => 0,
                'direction' => 'flat',
                'label' => 'No previous attendance data',
            ],
        ];
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

    private function weekLabel(string $startsOn, string $endsOn): string
    {
        return Carbon::parse($startsOn)->format('M j').' - '.Carbon::parse($endsOn)->format('M j, Y');
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
        return $time === null || $time === '' ? '' : substr($time, 0, 5);
    }

    private function attendanceTone(string $status): string
    {
        return match ($status) {
            'present', 'recorded' => 'green',
            'late' => 'orange',
            'absent' => 'red',
            default => 'blue',
        };
    }

    private function tone(mixed $tone): string
    {
        return in_array($tone, ['blue', 'green', 'orange', 'purple', 'red', 'teal'], true) ? (string) $tone : 'blue';
    }
}
