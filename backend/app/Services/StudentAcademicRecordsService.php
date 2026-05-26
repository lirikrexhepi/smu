<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class StudentAcademicRecordsService
{
    public const REQUIRED_ATTENDANCE_PERCENTAGE = 75;

    public function gradeAveragesSubquery(): Builder
    {
        return DB::table('course_grade_records')
            ->whereNotNull('grade')
            ->select('student_enrollment_id')
            ->selectRaw('ROUND(SUM(grade * COALESCE(weight, 1)) / NULLIF(SUM(COALESCE(weight, 1)), 0), 2) as numeric_grade')
            ->selectRaw('COUNT(grade) as grade_count')
            ->selectRaw('MAX(graded_on) as latest_graded_on')
            ->groupBy('student_enrollment_id');
    }

    public function attendanceStatsSubquery(): Builder
    {
        return DB::table('course_attendance_records')
            ->whereIn('status', ['present', 'absent', 'late', 'recorded'])
            ->select('student_enrollment_id')
            ->selectRaw('COUNT(*) as total_sessions')
            ->selectRaw("SUM(CASE WHEN status in ('present', 'late', 'recorded') THEN 1 ELSE 0 END) as sessions_attended")
            ->selectRaw("SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absences")
            ->selectRaw("SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_records")
            ->selectRaw("ROUND((SUM(CASE WHEN status in ('present', 'late', 'recorded') THEN 1 ELSE 0 END) * 100.0) / NULLIF(COUNT(*), 0)) as attendance_percentage")
            ->groupBy('student_enrollment_id');
    }

    public function gradeLabel(mixed $grade): string
    {
        if ($grade === null || $grade === '') {
            return '';
        }

        $number = (float) $grade;
        $rounded = max(5, min(10, (int) round($number)));

        return trim($this->decimalLabel($number).' '.$this->gradeDescription($rounded));
    }

    public function gradeDescription(int $grade): string
    {
        return match ($grade) {
            10 => 'Excellent',
            9 => 'Very Good',
            8 => 'Good',
            7 => 'Satisfactory',
            6 => 'Sufficient',
            default => 'Failed',
        };
    }

    public function gradeTone(mixed $grade): string
    {
        if ($grade === null || $grade === '') {
            return 'blue';
        }

        $number = (float) $grade;

        if ($number >= 8) {
            return 'green';
        }

        if ($number >= 6) {
            return 'orange';
        }

        return 'purple';
    }

    public function courseStatus(string $enrollmentStatus, mixed $grade): string
    {
        if ($grade === null || $grade === '') {
            return 'in-progress';
        }

        if ($enrollmentStatus !== 'completed') {
            return 'in-progress';
        }

        return (float) $grade >= 6 ? 'passed' : 'failed';
    }

    public function courseStatusLabel(string $status): string
    {
        return match ($status) {
            'passed' => 'Passed',
            'failed' => 'Failed',
            default => 'In progress',
        };
    }

    public function decimalLabel(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $number = (float) $value;

        return floor($number) === $number ? (string) (int) $number : number_format($number, 1);
    }

    public function attendanceStatus(int $percentage): string
    {
        return $percentage >= self::REQUIRED_ATTENDANCE_PERCENTAGE ? 'On track' : 'Needs attention';
    }
}
