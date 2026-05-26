<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class StudentCoursesService
{
    public function __construct(private readonly StudentAcademicRecordsService $records) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(Request $request): array
    {
        $user = $request->user();
        $student = DB::table('students')->where('user_id', $user?->id)->first();

        if ($student === null) {
            return $this->emptyOverview();
        }

        $courses = $this->overviewRows((int) $student->id, $request);
        $deadlines = $this->upcomingDeadlines((int) $student->id, $request);
        $semester = $this->currentSemester((int) $student->id);

        return [
            'semester' => (string) ($semester?->name ?? ''),
            'academicYear' => (string) ($semester?->academic_year_name ?? ''),
            'summary' => $this->overviewSummary($courses, $deadlines, (int) ($student->credits_required ?? 0)),
            'filters' => [
                'semesters' => $this->semesterOptions((int) $student->id),
                'statuses' => $this->statusOptions((int) $student->id),
            ],
            'courses' => $courses->map(fn (object $course): array => $this->overviewCoursePayload($course))->values()->all(),
            'upcomingDeadlines' => $deadlines,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function detail(Request $request, string $courseId): ?array
    {
        $user = $request->user();
        $student = DB::table('students')->where('user_id', $user?->id)->first();

        if ($student === null) {
            return null;
        }

        $course = $this->detailRow((int) $student->id, $courseId);

        if ($course === null) {
            return null;
        }

        $enrollmentId = (int) $course->enrollment_id;
        $courseDbId = (int) $course->course_db_id;

        return [
            'courseId' => (string) $course->course_key,
            'code' => (string) $course->code,
            'name' => (string) $course->name,
            'professor' => [
                'name' => (string) ($course->professor_name ?? ''),
                'email' => (string) ($course->professor_email ?? ''),
                'officeHours' => (string) ($course->office_hours ?? ''),
                'consultation' => (string) ($course->consultation ?? ''),
            ],
            'ects' => (int) $course->ects,
            'schedule' => $this->schedulePayload($course),
            'room' => (string) ($course->room ?? $course->schedule_room ?? ''),
            'semester' => (string) ($course->semester_name ?? ''),
            'status' => (string) ($course->course_status ?? ''),
            'description' => (string) ($course->description ?? ''),
            'overview' => [
                'learningOutcomes' => $this->jsonStringList($course->learning_outcomes),
                'topics' => $this->jsonStringList($course->topics),
                'gradingBreakdown' => (string) ($course->grading_breakdown ?? ''),
            ],
            'courseInfo' => $this->courseInfo($courseDbId),
            'materials' => $this->materials($courseDbId),
            'attendance' => $this->attendance($enrollmentId),
            'grades' => $this->grades($enrollmentId, $courseDbId, $course),
            'assessments' => $this->events($courseDbId, 'assessment')->map(fn (object $event): array => $this->assessmentPayload($event))->all(),
            'exams' => $this->events($courseDbId, 'exam')->map(fn (object $event): array => $this->examPayload($event))->all(),
            'announcements' => $this->announcements($courseDbId),
            'deadlines' => $this->events($courseDbId, 'deadline')->map(fn (object $event): array => $this->eventPayload($event))->all(),
            'enrollment' => [
                'status' => (string) $course->enrollment_status,
                'statusLabel' => (string) ($course->status_label ?: ucfirst((string) $course->enrollment_status)),
                'currentGrade' => $this->currentGradeLabel($course),
                'currentGradePoints' => $this->records->decimalLabel($course->numeric_grade),
                'attendancePercentage' => (int) ($course->attendance_percentage ?? 0),
                'nextImportantEventId' => (string) ($this->nextImportantEvent($courseDbId)?->event_key ?? ''),
                'enrolledAt' => (string) ($course->enrolled_on ?? ''),
            ],
        ];
    }

    /**
     * @return Collection<int, object>
     */
    private function overviewRows(int $studentId, Request $request): Collection
    {
        $query = $this->enrollmentBaseQuery($studentId);

        $this->applyCourseFilters($query, $request);

        $sort = (string) $request->query('sort', 'default');

        match ($sort) {
            'name-asc' => $query->orderBy('courses.name'),
            'name-desc' => $query->orderByDesc('courses.name'),
            'grade-desc' => $query->orderByDesc('grade_stats.numeric_grade'),
            'grade-asc' => $query->orderBy('grade_stats.numeric_grade'),
            'attendance-desc' => $query->orderByDesc('attendance_stats.attendance_percentage'),
            'attendance-asc' => $query->orderBy('attendance_stats.attendance_percentage'),
            'ects-desc' => $query->orderByDesc('courses.ects'),
            default => $query
                ->orderByRaw("case student_enrollments.status when 'active' then 1 when 'registered' then 2 when 'upcoming' then 3 when 'completed' then 4 else 5 end")
                ->orderByDesc('semesters.number')
                ->orderBy('courses.code'),
        };

        if ($sort !== 'default') {
            $query->orderBy('courses.code');
        }

        return $query->get();
    }

    private function detailRow(int $studentId, string $requested): ?object
    {
        $requested = trim($requested);

        return $this->enrollmentBaseQuery($studentId)
            ->where(function ($query) use ($requested): void {
                $query->where('courses.course_key', $requested)
                    ->orWhere('courses.code', $requested);

                if (ctype_digit($requested)) {
                    $query->orWhere('courses.id', (int) $requested);
                }
            })
            ->first();
    }

    private function currentSemester(int $studentId): ?object
    {
        return DB::table('student_enrollments')
            ->join('semesters', 'semesters.id', '=', 'student_enrollments.semester_id')
            ->leftJoin('academic_years', 'academic_years.id', '=', 'semesters.academic_year_id')
            ->where('student_enrollments.student_id', $studentId)
            ->where('student_enrollments.status', '!=', 'dropped')
            ->select('semesters.*', 'academic_years.name as academic_year_name')
            ->distinct()
            ->orderByDesc('semesters.is_current')
            ->orderByDesc('semesters.number')
            ->orderByDesc('semesters.id')
            ->first();
    }

    private function enrollmentBaseQuery(int $studentId): Builder
    {
        return DB::table('student_enrollments')
            ->join('courses', 'courses.id', '=', 'student_enrollments.course_id')
            ->leftJoin('semesters', 'semesters.id', '=', 'student_enrollments.semester_id')
            ->leftJoin('academic_years', 'academic_years.id', '=', 'semesters.academic_year_id')
            ->leftJoin('course_schedules', 'course_schedules.course_id', '=', 'courses.id')
            ->leftJoinSub($this->records->gradeAveragesSubquery(), 'grade_stats', function ($join): void {
                $join->on('grade_stats.student_enrollment_id', '=', 'student_enrollments.id');
            })
            ->leftJoinSub($this->records->attendanceStatsSubquery(), 'attendance_stats', function ($join): void {
                $join->on('attendance_stats.student_enrollment_id', '=', 'student_enrollments.id');
            })
            ->leftJoin('course_professor', function ($join): void {
                $join->on('course_professor.course_id', '=', 'courses.id')
                    ->where('course_professor.role', '=', 'instructor');
            })
            ->leftJoin('professors', 'professors.id', '=', 'course_professor.professor_id')
            ->leftJoin('users as professor_users', 'professor_users.id', '=', 'professors.user_id')
            ->where('student_enrollments.student_id', $studentId)
            ->where('student_enrollments.status', '!=', 'dropped')
            ->select(
                'student_enrollments.id as enrollment_id',
                'student_enrollments.status as enrollment_status',
                'student_enrollments.status_label',
                'student_enrollments.enrolled_on',
                'grade_stats.numeric_grade',
                'grade_stats.grade_count',
                'attendance_stats.attendance_percentage',
                'attendance_stats.total_sessions',
                'attendance_stats.sessions_attended',
                'attendance_stats.absences',
                'attendance_stats.late_records',
                'courses.id as course_db_id',
                'courses.course_key',
                'courses.code',
                'courses.name',
                'courses.ects',
                'courses.status as course_status',
                'courses.room',
                'courses.description',
                'courses.learning_outcomes',
                'courses.topics',
                'courses.grading_breakdown',
                'semesters.code as semester_code',
                'semesters.name as semester_name',
                'semesters.number as semester_number',
                'academic_years.name as academic_year_name',
                'course_schedules.days_label',
                'course_schedules.time_label',
                'course_schedules.room as schedule_room',
                'course_schedules.label as schedule_label',
                'professor_users.name as professor_name',
                'professor_users.email as professor_email',
                'professors.office_hours',
                'professors.consultation',
            );
    }

    private function applyCourseFilters(Builder $query, Request $request): void
    {
        $search = trim((string) $request->query('search', ''));
        $semester = trim((string) $request->query('semester', ''));
        $status = trim((string) $request->query('status', ''));

        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                $query->where('courses.name', 'like', '%'.$search.'%')
                    ->orWhere('courses.code', 'like', '%'.$search.'%')
                    ->orWhere('professor_users.name', 'like', '%'.$search.'%');
            });
        }

        if ($semester !== '' && $semester !== 'all') {
            $query->where(function ($query) use ($semester): void {
                $query->where('semesters.name', $semester)
                    ->orWhere('semesters.code', $semester);
            });
        }

        if ($status !== '' && $status !== 'all') {
            $query->where('student_enrollments.status', $status);
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
     * @return array<int, array{value: string, label: string}>
     */
    private function statusOptions(int $studentId): array
    {
        $labels = [
            'active' => 'Active',
            'registered' => 'Registered',
            'upcoming' => 'Upcoming',
            'completed' => 'Completed',
        ];

        return DB::table('student_enrollments')
            ->where('student_id', $studentId)
            ->where('status', '!=', 'dropped')
            ->select('status')
            ->distinct()
            ->orderByRaw("case status when 'active' then 1 when 'registered' then 2 when 'upcoming' then 3 when 'completed' then 4 else 5 end")
            ->pluck('status')
            ->map(fn (mixed $status): array => [
                'value' => (string) $status,
                'label' => $labels[(string) $status] ?? ucfirst((string) $status),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, object>  $courses
     * @param  array<int, array<string, mixed>>  $deadlines
     * @return array<string, mixed>
     */
    private function overviewSummary(Collection $courses, array $deadlines, int $ectsTarget): array
    {
        $grades = $courses
            ->map(fn (object $course): float => (float) ($course->numeric_grade ?? 0))
            ->filter(fn (float $grade): bool => $grade > 0)
            ->values();

        return [
            'enrolledCourses' => $courses->count(),
            'totalEcts' => (int) $courses->sum(fn (object $course): int => (int) $course->ects),
            'ectsTarget' => $ectsTarget > 0 ? $ectsTarget : 30,
            'upcomingDeadlines' => count($deadlines),
            'statusCounts' => [
                'active' => $courses->where('enrollment_status', 'active')->count(),
                'registered' => $courses->where('enrollment_status', 'registered')->count(),
                'upcoming' => $courses->where('enrollment_status', 'upcoming')->count(),
            ],
            'gradeStats' => [
                'average' => $grades->isEmpty() ? 0 : round((float) $grades->avg(), 2),
                'min' => $grades->isEmpty() ? 0 : round((float) $grades->min(), 2),
                'max' => $grades->isEmpty() ? 0 : round((float) $grades->max(), 2),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function upcomingDeadlines(int $studentId, Request $request): array
    {
        $query = DB::table('student_enrollments')
            ->join('courses', 'courses.id', '=', 'student_enrollments.course_id')
            ->leftJoin('semesters', 'semesters.id', '=', 'student_enrollments.semester_id')
            ->join('course_events', 'course_events.course_id', '=', 'courses.id')
            ->leftJoin('course_professor', function ($join): void {
                $join->on('course_professor.course_id', '=', 'courses.id')
                    ->where('course_professor.role', '=', 'instructor');
            })
            ->leftJoin('professors', 'professors.id', '=', 'course_professor.professor_id')
            ->leftJoin('users as professor_users', 'professor_users.id', '=', 'professors.user_id')
            ->where('student_enrollments.student_id', $studentId)
            ->where('student_enrollments.status', '!=', 'dropped')
            ->where('course_events.category', 'deadline')
            ->whereDate('course_events.event_date', '>=', Carbon::today()->toDateString())
            ->whereDate('course_events.event_date', '<=', Carbon::today()->addDays(14)->toDateString())
            ->select(
                'course_events.*',
                'courses.course_key',
                'courses.code as course_code',
                'courses.name as course_name',
            );

        $this->applyCourseFilters($query, $request);

        return $query
            ->orderBy('course_events.event_date')
            ->orderBy('course_events.event_time')
            ->orderBy('courses.code')
            ->get()
            ->map(fn (object $event): array => $this->eventPayload($event) + [
                'courseId' => (string) $event->course_key,
                'courseCode' => (string) $event->course_code,
                'courseName' => (string) $event->course_name,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function overviewCoursePayload(object $course): array
    {
        return [
            'courseId' => (string) $course->course_key,
            'code' => (string) $course->code,
            'name' => (string) $course->name,
            'professor' => (string) ($course->professor_name ?? ''),
            'ects' => (int) $course->ects,
            'schedule' => $this->schedulePayload($course),
            'room' => (string) ($course->room ?? $course->schedule_room ?? ''),
            'semester' => (string) ($course->semester_name ?? ''),
            'enrollmentStatus' => (string) $course->enrollment_status,
            'enrollmentStatusLabel' => (string) ($course->status_label ?: ucfirst((string) $course->enrollment_status)),
            'currentGrade' => $this->currentGradeLabel($course),
            'currentGradePoints' => $this->records->decimalLabel($course->numeric_grade),
            'attendancePercentage' => (int) ($course->attendance_percentage ?? 0),
            'nextImportantEvent' => $this->nextImportantEventPayload((int) $course->course_db_id),
        ];
    }

    private function currentGradeLabel(object $course): string
    {
        $label = $this->records->gradeLabel($course->numeric_grade);

        if ($label === '') {
            return '';
        }

        return $course->enrollment_status === 'completed' ? $label : $label.' projected';
    }

    /**
     * @return array<string, string>|null
     */
    private function nextImportantEventPayload(int $courseId): ?array
    {
        $event = $this->nextImportantEvent($courseId);

        if ($event === null) {
            return null;
        }

        return [
            'id' => (string) $event->event_key,
            'title' => (string) $event->title,
            'type' => (string) ($event->type ?? $event->category ?? ''),
            'date' => $this->eventDateLabel($event->event_date, $event->date_label),
            'time' => (string) ($event->time_label ?? $this->timeLabel($event->event_time)),
            'statusLabel' => (string) ($event->status_label ?? ''),
            'tone' => $this->tone($event->tone),
        ];
    }

    private function nextImportantEvent(int $courseId): ?object
    {
        return DB::table('course_events')
            ->where('course_id', $courseId)
            ->whereDate('event_date', '>=', Carbon::today()->toDateString())
            ->orderBy('event_date')
            ->orderBy('event_time')
            ->orderBy('id')
            ->first();
    }

    /**
     * @return array{days: string, time: string, room: string, label: string}
     */
    private function schedulePayload(object $course): array
    {
        $days = (string) ($course->days_label ?? '');
        $time = (string) ($course->time_label ?? '');
        $room = (string) ($course->schedule_room ?? $course->room ?? '');
        $type = (string) ($course->schedule_label ?? 'Lecture');

        return [
            'days' => $days,
            'time' => $time,
            'room' => $room,
            'label' => trim($days.' '.$time.($room !== '' ? ' / '.$room : '')) ?: $type,
        ];
    }

    /**
     * @return array<int, array{id: string, label: string, value: string}>
     */
    private function courseInfo(int $courseId): array
    {
        $course = DB::table('courses')
            ->leftJoin('semesters', 'semesters.id', '=', 'courses.semester_id')
            ->where('courses.id', $courseId)
            ->select('courses.ects', 'courses.room', 'courses.grading_breakdown', 'semesters.name as semester_name')
            ->first();

        if ($course === null) {
            return [];
        }

        return [
            ['id' => 'credits', 'label' => 'ECTS', 'value' => (string) $course->ects],
            ['id' => 'semester', 'label' => 'Semester', 'value' => (string) ($course->semester_name ?? '')],
            ['id' => 'room', 'label' => 'Room', 'value' => (string) ($course->room ?? '')],
            ['id' => 'assessment', 'label' => 'Assessment', 'value' => (string) ($course->grading_breakdown ?? '')],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function materials(int $courseId): array
    {
        return DB::table('course_materials')
            ->where('course_id', $courseId)
            ->orderByDesc('published_at')
            ->orderBy('id')
            ->get()
            ->map(fn (object $material): array => [
                'id' => (string) $material->material_key,
                'title' => (string) $material->title,
                'type' => (string) ($material->type ?? ''),
                'updatedAt' => $this->dateLabel($material->published_at),
                'size' => (string) ($material->size_label ?? ''),
                'downloadUrl' => (string) ($material->download_url ?? ''),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function attendance(int $enrollmentId): array
    {
        $summary = DB::query()
            ->fromSub($this->records->attendanceStatsSubquery(), 'attendance_stats')
            ->where('student_enrollment_id', $enrollmentId)
            ->first();

        $records = DB::table('course_attendance_records')
            ->where('student_enrollment_id', $enrollmentId)
            ->orderByDesc('held_on')
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(fn (object $record): array => [
                'id' => (string) ($record->record_key ?? $record->id),
                'date' => (string) ($record->date_label ?: $this->dateLabel($record->held_on)),
                'type' => (string) ($record->type ?? ''),
                'status' => (string) ($record->status_label ?: ucfirst((string) $record->status)),
            ])
            ->all();

        if ($summary === null) {
            return [
                'percentage' => 0,
                'requiredPercentage' => StudentAcademicRecordsService::REQUIRED_ATTENDANCE_PERCENTAGE,
                'sessionsHeld' => 0,
                'sessionsAttended' => 0,
                'status' => 'No attendance recorded',
                'summary' => [],
                'records' => $records,
            ];
        }

        return [
            'percentage' => (int) ($summary->attendance_percentage ?? 0),
            'requiredPercentage' => StudentAcademicRecordsService::REQUIRED_ATTENDANCE_PERCENTAGE,
            'sessionsHeld' => (int) ($summary->total_sessions ?? 0),
            'sessionsAttended' => (int) ($summary->sessions_attended ?? 0),
            'status' => $this->records->attendanceStatus((int) ($summary->attendance_percentage ?? 0)),
            'summary' => [
                ['label' => 'Present', 'value' => (string) ($summary->sessions_attended ?? 0)],
                ['label' => 'Missed', 'value' => (string) ($summary->absences ?? 0)],
            ],
            'records' => $records,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function grades(int $enrollmentId, int $courseId, object $course): array
    {
        return [
            'currentGrade' => $this->currentGradeLabel($course),
            'currentGradePoints' => $this->records->decimalLabel($course->numeric_grade),
            'scale' => '5-10 numeric scale',
            'breakdown' => DB::table('course_grade_components')
                ->where('course_id', $courseId)
                ->orderBy('id')
                ->get()
                ->map(fn (object $component): array => [
                    'component' => (string) $component->component,
                    'weight' => (int) $component->weight,
                ])
                ->all(),
            'records' => DB::table('course_grade_records')
                ->where('student_enrollment_id', $enrollmentId)
                ->orderByDesc('graded_on')
                ->orderByDesc('id')
                ->get()
                ->map(fn (object $record): array => [
                    'id' => (string) ($record->grade_key ?? $record->id),
                    'title' => (string) $record->title,
                    'type' => (string) ($record->type ?? ''),
                    'score' => $this->records->decimalLabel($record->grade).'/10',
                    'weight' => $record->weight === null ? '' : ((string) $record->weight).'%',
                    'date' => (string) ($record->date_label ?: $this->dateLabel($record->graded_on)),
                    'status' => (string) ($record->status ?? ''),
                ])
                ->all(),
        ];
    }

    /**
     * @return Collection<int, object>
     */
    private function events(int $courseId, string $category): Collection
    {
        return DB::table('course_events')
            ->where('course_id', $courseId)
            ->where('category', $category)
            ->orderBy('event_date')
            ->orderBy('event_time')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function assessmentPayload(object $event): array
    {
        return $this->eventPayload($event) + [
            'mode' => (string) ($event->mode ?? ''),
            'description' => (string) ($event->description ?? ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function examPayload(object $event): array
    {
        return $this->eventPayload($event) + [
            'duration' => (string) ($event->duration ?? ''),
            'room' => (string) ($event->room ?? ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function eventPayload(object $event): array
    {
        return [
            'id' => (string) $event->event_key,
            'title' => (string) $event->title,
            'type' => (string) ($event->type ?? $event->category ?? ''),
            'date' => $this->eventDateLabel($event->event_date, $event->date_label),
            'time' => (string) ($event->time_label ?? $this->timeLabel($event->event_time)),
            'statusLabel' => (string) ($event->status_label ?? ''),
            'tone' => $this->tone($event->tone),
        ];
    }

    /**
     * @return array<int, array{id: string, title: string, body: string, date: string}>
     */
    private function announcements(int $courseId): array
    {
        return DB::table('course_announcements')
            ->where('course_id', $courseId)
            ->orderByDesc('published_on')
            ->orderByDesc('id')
            ->get()
            ->map(fn (object $announcement): array => [
                'id' => (string) $announcement->announcement_key,
                'title' => (string) $announcement->title,
                'body' => (string) $announcement->body,
                'date' => (string) ($announcement->date_label ?: $this->dateLabel($announcement->published_on)),
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function jsonStringList(mixed $value): array
    {
        return collect($this->jsonArray($value))
            ->filter(fn (mixed $item): bool => is_scalar($item))
            ->map(fn (mixed $item): string => (string) $item)
            ->values()
            ->all();
    }

    /**
     * @return array<int|string, mixed>
     */
    private function jsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function eventDateLabel(mixed $date, mixed $fallback): string
    {
        if ($date !== null && $date !== '') {
            return Carbon::parse($date)->format('j M');
        }

        return (string) ($fallback ?? '');
    }

    private function dateLabel(mixed $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        return Carbon::parse($date)->format('M j, Y');
    }

    private function timeLabel(mixed $time): string
    {
        if ($time === null || $time === '') {
            return '';
        }

        return substr((string) $time, 0, 5);
    }

    private function decimalLabel(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $number = (float) $value;

        return floor($number) === $number ? (string) (int) $number : number_format($number, 1);
    }

    private function tone(mixed $tone): string
    {
        return in_array($tone, ['blue', 'green', 'orange', 'purple'], true) ? (string) $tone : 'blue';
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyOverview(): array
    {
        return [
            'semester' => '',
            'academicYear' => '',
            'summary' => [
                'enrolledCourses' => 0,
                'totalEcts' => 0,
                'ectsTarget' => 0,
                'upcomingDeadlines' => 0,
                'statusCounts' => ['active' => 0, 'registered' => 0, 'upcoming' => 0],
                'gradeStats' => ['average' => 0, 'min' => 0, 'max' => 0],
            ],
            'filters' => ['semesters' => [], 'statuses' => []],
            'courses' => [],
            'upcomingDeadlines' => [],
        ];
    }
}
