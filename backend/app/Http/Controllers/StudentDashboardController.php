<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class StudentDashboardController
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $student = DB::table('students')->where('user_id', $user?->id)->first();
        $studentId = $student?->id;

        return ApiResponse::success([
            'studentName' => $user?->name ?? '',
            'semester' => $student?->current_semester_label ?? '',
            'academicTerm' => $student?->academic_year_label ?? '',
            'metrics' => $studentId === null ? [] : $this->metrics($studentId),
            'todaysClasses' => $studentId === null ? [] : $this->todaysClasses($studentId),
            'upcomingDeadlines' => $studentId === null ? [] : $this->upcomingDeadlines($studentId),
            'latestGrades' => $studentId === null ? [] : $this->latestGrades($studentId),
            'attendanceWarning' => $studentId === null ? $this->emptyAttendanceWarning() : $this->attendanceWarning($studentId),
            'attendanceSummary' => $studentId === null ? [] : $this->attendanceSummary($studentId),
        ], 'Student dashboard loaded');
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function metrics(int $studentId): array
    {
        return DB::table('student_dashboard_metrics')
            ->where('student_id', $studentId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (object $metric): array => [
                'id' => (string) $metric->metric_key,
                'label' => (string) $metric->label,
                'value' => (string) $metric->value,
                'helper' => (string) ($metric->helper ?? ''),
                'tone' => $this->tone($metric->tone),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function todaysClasses(int $studentId): array
    {
        return DB::table('student_dashboard_classes')
            ->where('student_id', $studentId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (object $class): array => [
                'id' => (string) $class->class_key,
                'time' => (string) $class->time_label,
                'courseCode' => (string) $class->course_code,
                'courseName' => (string) $class->course_name,
                'room' => (string) ($class->room ?? ''),
                'type' => (string) ($class->type ?? ''),
                'tone' => $this->tone($class->tone),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function upcomingDeadlines(int $studentId): array
    {
        return DB::table('student_dashboard_deadlines')
            ->where('student_id', $studentId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (object $deadline): array => [
                'id' => (string) $deadline->deadline_key,
                'title' => (string) $deadline->title,
                'courseCode' => (string) $deadline->course_code,
                'date' => (string) ($deadline->date_label ?? ''),
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
        return DB::table('student_dashboard_latest_grades')
            ->where('student_id', $studentId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (object $grade): array => [
                'id' => (string) $grade->grade_key,
                'course' => (string) $grade->course,
                'assessment' => (string) $grade->assessment,
                'type' => (string) ($grade->type ?? ''),
                'grade' => (string) ($grade->grade ?? ''),
                'date' => (string) ($grade->date_label ?? ''),
                'tone' => $this->tone($grade->tone),
            ])
            ->all();
    }

    /**
     * @return array<string, string|int>
     */
    private function attendanceWarning(int $studentId): array
    {
        $warning = DB::table('student_dashboard_attendance_warnings')
            ->where('student_id', $studentId)
            ->first();

        if ($warning === null) {
            return $this->emptyAttendanceWarning();
        }

        return [
            'courseCode' => (string) $warning->course_code,
            'courseName' => (string) $warning->course_name,
            'rate' => (int) $warning->rate,
            'requiredRate' => (int) $warning->required_rate,
            'message' => (string) $warning->message,
            'detail' => (string) ($warning->detail ?? ''),
        ];
    }

    /**
     * @return array<int, array{courseName: string, rate: int}>
     */
    private function attendanceSummary(int $studentId): array
    {
        return DB::table('student_dashboard_attendance_summaries')
            ->where('student_id', $studentId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (object $summary): array => [
                'courseName' => (string) $summary->course_name,
                'rate' => (int) $summary->rate,
            ])
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
            'requiredRate' => 75,
            'message' => 'No attendance warnings',
            'detail' => 'Your attendance is currently within the required range.',
        ];
    }

    private function tone(mixed $tone): string
    {
        return in_array($tone, ['blue', 'green', 'orange', 'purple'], true) ? $tone : 'blue';
    }
}
