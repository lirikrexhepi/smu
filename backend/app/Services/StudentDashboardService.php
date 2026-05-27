<?php

namespace App\Services;

use App\Models\Identity\Student;
use App\Models\Gradebook\StudentEnrollment;
use App\Models\Gradebook\CourseGradeRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class StudentDashboardService
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
            return [
                'studentName' => $user?->name ?? '',
                'semester' => '',
                'academicTerm' => '',
                'metrics' => [],
                'todaysClasses' => [],
                'upcomingDeadlines' => [],
                'latestGrades' => [],
                'attendanceWarning' => $this->emptyAttendanceWarning(),
                'attendanceSummary' => [],
            ];
        }

        $semester = $this->currentSemester((int) $student->id);

        return [
            'studentName' => $user?->name ?? '',
            'semester' => (string) ($semester?->name ?? ''),
            'academicTerm' => (string) ($semester?->academic_year_name ?? ''),
            'metrics' => $this->metrics((int) $student->id, $semester?->id),
            'todaysClasses' => $this->todaysClasses((int) $student->id, $semester?->id),
            'upcomingDeadlines' => $this->upcomingDeadlines((int) $student->id),
            'latestGrades' => $this->latestGrades((int) $student->id),
            'attendanceWarning' => $this->attendanceWarning((int) $student->id, $semester?->id),
            'attendanceSummary' => $this->attendanceSummary((int) $student->id, $semester?->id),
        ];
    }

    private function currentSemester(int $studentId): ?object
    {
        return StudentEnrollment::join('semesters', 'semesters.id', '=', 'student_enrollments.semester_id')
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

    /**
     * @return array<int, array<string, string>>
     */
    private function metrics(int $studentId, ?int $semesterId): array
    {
        $currentRows = $this->enrollmentRows($studentId, $semesterId)
            ->filter(fn (object $row): bool => $row->enrollment_status !== 'completed')
            ->values();
        $completedRows = $this->enrollmentRows($studentId, null)
            ->filter(fn (object $row): bool => $row->enrollment_status === 'completed' && (float) ($row->numeric_grade ?? 0) >= 6)
            ->values();
        $grades = $currentRows
            ->map(fn (object $row): float => (float) ($row->numeric_grade ?? 0))
            ->filter(fn (float $grade): bool => $grade > 0);
        $attendance = $currentRows
            ->map(fn (object $row): int => (int) ($row->attendance_percentage ?? 0))
            ->filter(fn (int $percentage): bool => $percentage > 0);

        return [
            [
                'id' => 'average-grade',
                'label' => 'Average Grade',
                'value' => $grades->isEmpty() ? '0' : $this->records->decimalLabel(round((float) $grades->avg(), 2)),
                'helper' => 'Current semester grade records',
                'tone' => 'blue',
            ],
            [
                'id' => 'credits-earned',
                'label' => 'Credits Earned',
                'value' => (string) $completedRows->sum('ects'),
                'helper' => $completedRows->count().' completed courses',
                'tone' => 'green',
            ],
            [
                'id' => 'active-courses',
                'label' => 'Active Courses',
                'value' => (string) $currentRows->count(),
                'helper' => 'From enrollments',
                'tone' => 'purple',
            ],
            [
                'id' => 'attendance',
                'label' => 'Attendance',
                'value' => ($attendance->isEmpty() ? 0 : (int) round((float) $attendance->avg())).'%',
                'helper' => 'Current semester records',
                'tone' => 'orange',
            ],
        ];
    }

    /**
     * @return Collection<int, object>
     */
    private function enrollmentRows(int $studentId, ?int $semesterId): Collection
    {
        return StudentEnrollment::join('courses', 'courses.id', '=', 'student_enrollments.course_id')
            ->leftJoin('semesters', 'semesters.id', '=', 'student_enrollments.semester_id')
            ->leftJoinSub($this->records->gradeAveragesSubquery(), 'grade_stats', function ($join): void {
                $join->on('grade_stats.student_enrollment_id', '=', 'student_enrollments.id');
            })
            ->leftJoinSub($this->records->attendanceStatsSubquery(), 'attendance_stats', function ($join): void {
                $join->on('attendance_stats.student_enrollment_id', '=', 'student_enrollments.id');
            })
            ->where('student_enrollments.student_id', $studentId)
            ->where('student_enrollments.status', '!=', 'dropped')
            ->when($semesterId !== null, fn ($query) => $query->where('student_enrollments.semester_id', $semesterId))
            ->select(
                'student_enrollments.id as enrollment_id',
                'student_enrollments.status as enrollment_status',
                'courses.id as course_id',
                'courses.course_key',
                'courses.code',
                'courses.name',
                'courses.ects',
                'semesters.number as semester_number',
                'grade_stats.numeric_grade',
                'attendance_stats.attendance_percentage',
            )
            ->orderByDesc('semesters.number')
            ->orderBy('courses.code')
            ->get();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function todaysClasses(int $studentId, ?int $semesterId): array
    {
        $today = Carbon::today();
        $dayName = $today->format('l');

        return StudentEnrollment::join('courses', 'courses.id', '=', 'student_enrollments.course_id')
            ->join('course_schedules', 'course_schedules.course_id', '=', 'courses.id')
            ->where('student_enrollments.student_id', $studentId)
            ->whereIn('student_enrollments.status', ['active', 'registered', 'upcoming'])
            ->when($semesterId !== null, fn ($query) => $query->where('student_enrollments.semester_id', $semesterId))
            ->select(
                'course_schedules.id',
                'course_schedules.days',
                'course_schedules.time_label',
                'course_schedules.starts_at',
                'course_schedules.ends_at',
                'course_schedules.room',
                'course_schedules.label',
                'courses.course_key',
                'courses.code',
                'courses.name',
            )
            ->orderBy('course_schedules.starts_at')
            ->orderBy('courses.code')
            ->get()
            ->filter(fn (object $class): bool => in_array($dayName, $this->jsonArray($class->days), true))
            ->map(fn (object $class): array => [
                'id' => 'today-'.$class->course_key.'-'.$class->id,
                'time' => (string) ($class->time_label ?: $this->timeRange($class->starts_at, $class->ends_at)),
                'courseCode' => (string) $class->code,
                'courseName' => (string) $class->name,
                'room' => (string) ($class->room ?? ''),
                'type' => (string) ($class->label ?? 'Lecture'),
                'tone' => 'blue',
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function upcomingDeadlines(int $studentId): array
    {
        return StudentEnrollment::join('courses', 'courses.id', '=', 'student_enrollments.course_id')
            ->join('course_events', 'course_events.course_id', '=', 'courses.id')
            ->where('student_enrollments.student_id', $studentId)
            ->where('student_enrollments.status', '!=', 'dropped')
            ->where('course_events.category', 'deadline')
            ->whereDate('course_events.event_date', '>=', Carbon::today()->toDateString())
            ->orderBy('course_events.event_date')
            ->orderBy('course_events.event_time')
            ->orderBy('courses.code')
            ->limit(6)
            ->select('course_events.*', 'courses.code as course_code')
            ->get()
            ->map(fn (object $deadline): array => [
                'id' => (string) $deadline->event_key.'-'.$deadline->course_code,
                'title' => (string) $deadline->title,
                'courseCode' => (string) $deadline->course_code,
                'date' => $this->eventDateLabel($deadline->event_date, $deadline->date_label),
                'statusLabel' => (string) ($deadline->status_label ?? ''),
                'tone' => $this->tone($deadline->tone),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function latestGrades(int $studentId): array
    {
        return CourseGradeRecord::join('student_enrollments', 'student_enrollments.id', '=', 'course_grade_records.student_enrollment_id')
            ->join('courses', 'courses.id', '=', 'student_enrollments.course_id')
            ->where('student_enrollments.student_id', $studentId)
            ->whereNotNull('course_grade_records.grade')
            ->orderByDesc('course_grade_records.graded_on')
            ->orderByDesc('course_grade_records.id')
            ->limit(4)
            ->select(
                'course_grade_records.*',
                'courses.code as course_code',
            )
            ->get()
            ->map(fn (object $grade): array => [
                'id' => (string) ($grade->grade_key ?: $grade->id).'-'.$grade->course_code,
                'course' => (string) $grade->course_code,
                'assessment' => (string) $grade->title,
                'type' => (string) ($grade->type ?? ''),
                'grade' => $this->records->gradeLabel($grade->grade),
                'date' => (string) ($grade->date_label ?: $this->dateLabel($grade->graded_on)),
                'tone' => $this->records->gradeTone($grade->grade),
            ])
            ->all();
    }

    /**
     * @return array<string, string|int>
     */
    private function attendanceWarning(int $studentId, ?int $semesterId): array
    {
        $lowest = $this->enrollmentRows($studentId, $semesterId)
            ->filter(fn (object $row): bool => $row->enrollment_status !== 'completed')
            ->sortBy(fn (object $row): int => (int) ($row->attendance_percentage ?? 0))
            ->first();

        if ($lowest === null || $lowest->attendance_percentage === null) {
            return $this->emptyAttendanceWarning();
        }

        $rate = (int) $lowest->attendance_percentage;
        $belowRequirement = $rate < StudentAcademicRecordsService::REQUIRED_ATTENDANCE_PERCENTAGE;

        return [
            'courseCode' => (string) $lowest->code,
            'courseName' => (string) $lowest->name,
            'rate' => $rate,
            'requiredRate' => StudentAcademicRecordsService::REQUIRED_ATTENDANCE_PERCENTAGE,
            'message' => $belowRequirement ? 'Attendance below requirement' : 'Lowest current attendance',
            'detail' => $belowRequirement
                ? 'Attend upcoming sessions to recover the required minimum.'
                : 'This is calculated from your course attendance records.',
        ];
    }

    /**
     * @return array<int, array{courseName: string, rate: int}>
     */
    private function attendanceSummary(int $studentId, ?int $semesterId): array
    {
        return $this->enrollmentRows($studentId, $semesterId)
            ->filter(fn (object $row): bool => $row->enrollment_status !== 'completed')
            ->map(fn (object $row): array => [
                'courseName' => (string) $row->code,
                'rate' => (int) ($row->attendance_percentage ?? 0),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, string|int>
     */
    private function emptyAttendanceWarning(): array
    {
        return [
            'courseCode' => '',
            'courseName' => 'Attendance',
            'rate' => 100,
            'requiredRate' => StudentAcademicRecordsService::REQUIRED_ATTENDANCE_PERCENTAGE,
            'message' => 'No attendance warnings',
            'detail' => 'No attendance records require attention.',
        ];
    }

    private function eventDateLabel(mixed $date, mixed $fallback): string
    {
        return $date === null || $date === '' ? (string) ($fallback ?? '') : Carbon::parse($date)->format('M j, Y');
    }

    private function dateLabel(mixed $date): string
    {
        return $date === null || $date === '' ? '' : Carbon::parse($date)->format('M j, Y');
    }

    private function timeRange(mixed $startsAt, mixed $endsAt): string
    {
        $start = $startsAt === null || $startsAt === '' ? '' : substr((string) $startsAt, 0, 5);
        $end = $endsAt === null || $endsAt === '' ? '' : substr((string) $endsAt, 0, 5);

        return trim($start.' - '.$end, ' -');
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

    private function tone(mixed $tone): string
    {
        return in_array($tone, ['blue', 'green', 'orange', 'purple'], true) ? (string) $tone : 'blue';
    }
}
