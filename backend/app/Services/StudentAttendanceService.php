<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class StudentAttendanceService
{
    /**
     * @return array<string, mixed>
     */
    public function forRequest(Request $request): array
    {
        $user = $request->user();
        $student = DB::table('students')->where('user_id', $user?->id)->first();

        if ($student === null) {
            return $this->emptyResponse($user);
        }

        $semester = $this->resolveSemester($student->id, (string) $request->query('semester', ''));
        $course = $this->resolveCourse($student->id, (string) $request->query('courseId', ''), $semester?->id);
        $week = $this->resolveWeek($student->id, $semester?->id, (string) $request->query('week', ''));

        return [
            'studentKey' => (string) ($user?->public_id ?? $student->student_key ?? ''),
            'semester' => (string) ($student->current_semester_label ?? $semester?->name ?? ''),
            'academicYear' => (string) ($student->academic_year_label ?? $semester?->academic_year_name ?? ''),
            'selectedCourseId' => $course === null ? null : (string) $course->course_key,
            'selectedSemester' => (string) ($semester?->name ?? ''),
            'selectedWeek' => (string) ($request->query('week', '') ?: ($week?->starts_on ?? '')),
            'filters' => [
                'courses' => $this->courseOptions($student->id, $semester?->id),
                'semesters' => $this->semesterOptions($student->id),
            ],
            'week' => $this->weekPayload($week, (string) $request->query('week', '')),
            'summary' => $this->summary($student->id, $course?->id, $semester?->id),
            'lastRecorded' => $this->lastRecorded($student->id, $course?->id, $semester?->id),
            'weeklySchedule' => $week === null ? [] : $this->weeklySchedule($student->id, $week, $course?->id, $semester?->id),
            'history' => $this->history($student->id, $course?->id, $semester?->id),
        ];
    }

    private function resolveSemester(int $studentId, string $requested): ?object
    {
        $requested = trim($requested);

        $query = DB::table('student_enrollments')
            ->join('semesters', 'semesters.id', '=', 'student_enrollments.semester_id')
            ->leftJoin('academic_years', 'academic_years.id', '=', 'semesters.academic_year_id')
            ->where('student_enrollments.student_id', $studentId)
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

        return DB::table('student_enrollments')
            ->join('courses', 'courses.id', '=', 'student_enrollments.course_id')
            ->where('student_enrollments.student_id', $studentId)
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

    private function resolveWeek(int $studentId, ?int $semesterId, string $requested): ?object
    {
        $requestedDate = $this->requestedWeekDate($requested);
        $baseQuery = DB::table('attendance_weeks')
            ->where('student_id', $studentId)
            ->when($semesterId !== null, fn ($query) => $query->where('semester_id', $semesterId));

        if ($requestedDate !== null) {
            $week = (clone $baseQuery)
                ->where('starts_on', '<=', $requestedDate->toDateString())
                ->where('ends_on', '>=', $requestedDate->toDateString())
                ->orderByDesc('starts_on')
                ->first();

            if ($week !== null) {
                return $week;
            }
        }

        $today = Carbon::today();
        $currentWeek = (clone $baseQuery)
            ->where('starts_on', '<=', $today->toDateString())
            ->where('ends_on', '>=', $today->toDateString())
            ->orderByDesc('starts_on')
            ->first();

        if ($currentWeek !== null) {
            return $currentWeek;
        }

        return $baseQuery
            ->orderByDesc('starts_on')
            ->first();
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
        return DB::table('student_enrollments')
            ->join('semesters', 'semesters.id', '=', 'student_enrollments.semester_id')
            ->where('student_enrollments.student_id', $studentId)
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
        return DB::table('student_enrollments')
            ->join('courses', 'courses.id', '=', 'student_enrollments.course_id')
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
    private function weekPayload(?object $week, string $requested): array
    {
        if ($week === null) {
            return [
                'startDate' => '',
                'endDate' => '',
                'label' => '',
                'requestedDate' => $requested !== '' ? $requested : null,
            ];
        }

        return [
            'startDate' => (string) $week->starts_on,
            'endDate' => (string) $week->ends_on,
            'label' => (string) ($week->label ?: $this->weekLabel($week->starts_on, $week->ends_on)),
            'requestedDate' => $requested !== '' ? $requested : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(int $studentId, ?int $courseId, ?int $semesterId): array
    {
        $summary = DB::table('attendance_summaries')
            ->where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->when($semesterId !== null, fn ($query) => $query->where('semester_id', $semesterId))
            ->when($semesterId === null, fn ($query) => $query->whereNull('semester_id'))
            ->first();

        if ($summary === null && $semesterId !== null) {
            $summary = DB::table('attendance_summaries')
                ->where('student_id', $studentId)
                ->where('course_id', $courseId)
                ->first();
        }

        if ($summary === null) {
            return $this->emptySummary();
        }

        $totalSessions = (int) $summary->total_sessions;
        $absences = (int) $summary->absences;
        $lateRecords = (int) $summary->late_records;

        return [
            'overallAttendance' => (int) $summary->overall_attendance,
            'presentSessions' => (int) $summary->present_sessions,
            'totalSessions' => $totalSessions,
            'absences' => $absences,
            'lateRecords' => $lateRecords,
            'absenceRate' => $totalSessions > 0 ? (int) round(($absences / $totalSessions) * 100) : 0,
            'lateRate' => $totalSessions > 0 ? (int) round(($lateRecords / $totalSessions) * 100) : 0,
            'comparisonVsLast4Weeks' => [
                'value' => abs((int) $summary->comparison_value),
                'direction' => $this->comparisonDirection($summary->comparison_direction),
                'label' => (string) ($summary->comparison_label ?: 'vs previous 4 weeks'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function lastRecorded(int $studentId, ?int $courseId, ?int $semesterId): ?array
    {
        $record = DB::table('attendance_last_recorded')
            ->join('courses', 'courses.id', '=', 'attendance_last_recorded.course_id')
            ->where('attendance_last_recorded.student_id', $studentId)
            ->when($courseId !== null, fn ($query) => $query->where('attendance_last_recorded.course_id', $courseId))
            ->when($semesterId !== null, fn ($query) => $query->where('courses.semester_id', $semesterId))
            ->orderByDesc('attendance_last_recorded.recorded_on')
            ->orderByDesc('attendance_last_recorded.time_label')
            ->orderByDesc('attendance_last_recorded.id')
            ->select(
                'attendance_last_recorded.*',
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
            'date' => (string) ($record->recorded_on ?? ''),
            'dateLabel' => (string) ($record->date_label ?: $this->dateLabel($record->recorded_on)),
            'time' => (string) ($record->time_label ?? ''),
            'status' => (string) $record->status,
            'statusLabel' => (string) ($record->status_label ?: ucfirst((string) $record->status)),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function weeklySchedule(int $studentId, object $week, ?int $courseId, ?int $semesterId): array
    {
        $days = $this->scheduleDays($week);
        $blocks = $this->storedScheduleBlocks($days->pluck('id')->filter()->all(), $courseId);

        if ($blocks->isEmpty()) {
            $blocks = $this->derivedScheduleBlocks($studentId, $week, $courseId, $semesterId);
        }

        return $days
            ->map(fn (object $day): array => [
                'date' => (string) $day->day_on,
                'dayName' => (string) ($day->day_name ?: Carbon::parse($day->day_on)->format('l')),
                'dayShort' => (string) ($day->day_short ?: Carbon::parse($day->day_on)->format('D')),
                'dateLabel' => (string) ($day->date_label ?: Carbon::parse($day->day_on)->format('M j')),
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
        $stored = DB::table('attendance_schedule_days')
            ->where('attendance_week_id', $week->id)
            ->orderBy('day_on')
            ->get();

        if ($stored->isNotEmpty()) {
            return $stored;
        }

        $days = collect();
        $day = Carbon::parse($week->starts_on)->startOfDay();
        $end = Carbon::parse($week->ends_on)->startOfDay();

        while ($day->lte($end)) {
            $days->push((object) [
                'id' => null,
                'day_on' => $day->toDateString(),
                'day_name' => $day->format('l'),
                'day_short' => $day->format('D'),
                'date_label' => $day->format('M j'),
            ]);
            $day->addDay();
        }

        return $days;
    }

    /**
     * @param  array<int, int>  $dayIds
     */
    private function storedScheduleBlocks(array $dayIds, ?int $courseId): Collection
    {
        if ($dayIds === []) {
            return collect();
        }

        return DB::table('attendance_schedule_blocks')
            ->join('attendance_schedule_days', 'attendance_schedule_days.id', '=', 'attendance_schedule_blocks.attendance_schedule_day_id')
            ->join('courses', 'courses.id', '=', 'attendance_schedule_blocks.course_id')
            ->leftJoin('course_professor', function ($join): void {
                $join->on('course_professor.course_id', '=', 'courses.id')
                    ->where('course_professor.role', '=', 'instructor');
            })
            ->leftJoin('professors', 'professors.id', '=', 'course_professor.professor_id')
            ->leftJoin('users as professor_users', 'professor_users.id', '=', 'professors.user_id')
            ->whereIn('attendance_schedule_blocks.attendance_schedule_day_id', $dayIds)
            ->when($courseId !== null, fn ($query) => $query->where('attendance_schedule_blocks.course_id', $courseId))
            ->select(
                'attendance_schedule_blocks.id',
                'attendance_schedule_days.day_on',
                'courses.course_key',
                'courses.code as course_code',
                'courses.name as course_name',
                'professor_users.name as professor_name',
                'attendance_schedule_blocks.time_label',
                'attendance_schedule_blocks.starts_at',
                'attendance_schedule_blocks.ends_at',
                'attendance_schedule_blocks.room',
                'attendance_schedule_blocks.type',
                'attendance_schedule_blocks.status',
                'attendance_schedule_blocks.status_label',
                'attendance_schedule_blocks.tone',
            )
            ->get();
    }

    private function derivedScheduleBlocks(int $studentId, object $week, ?int $courseId, ?int $semesterId): Collection
    {
        $schedules = DB::table('student_enrollments')
            ->join('courses', 'courses.id', '=', 'student_enrollments.course_id')
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
                'courses.id as course_id',
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

        $blocks = collect();
        $day = Carbon::parse($week->starts_on)->startOfDay();
        $end = Carbon::parse($week->ends_on)->startOfDay();

        while ($day->lte($end)) {
            $dayName = $day->format('l');

            foreach ($schedules as $schedule) {
                $scheduleDays = $this->jsonArray($schedule->days);

                if (! in_array($dayName, $scheduleDays, true)) {
                    continue;
                }

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
                    'status' => 'scheduled',
                    'status_label' => 'Scheduled',
                    'tone' => 'blue',
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
        return DB::table('attendance_history_records')
            ->join('courses', 'courses.id', '=', 'attendance_history_records.course_id')
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
            ->get()
            ->map(fn (object $record): array => [
                'id' => (string) ($record->record_key ?: $record->id),
                'courseId' => (string) $record->course_key,
                'courseCode' => (string) $record->course_code,
                'courseName' => (string) $record->course_name,
                'date' => (string) ($record->date_label ?: $this->dateLabel($record->recorded_on)),
                'dateIso' => (string) ($record->recorded_on ?? ''),
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
            'filters' => [
                'courses' => [],
                'semesters' => [],
            ],
            'week' => [
                'startDate' => '',
                'endDate' => '',
                'label' => '',
                'requestedDate' => null,
            ],
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
    private function jsonArray(?string $value): array
    {
        if ($value === null || $value === '') {
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
        $start = $this->timeValue($startsAt);
        $end = $this->timeValue($endsAt);

        return trim($start.' - '.$end, ' -');
    }

    private function timeValue(?string $time): string
    {
        return $time === null || $time === '' ? '' : substr($time, 0, 5);
    }

    private function comparisonDirection(mixed $direction): string
    {
        return in_array($direction, ['up', 'down', 'flat'], true) ? (string) $direction : 'flat';
    }

    private function tone(mixed $tone): string
    {
        return in_array($tone, ['blue', 'green', 'orange', 'purple', 'red', 'teal'], true) ? (string) $tone : 'blue';
    }
}
