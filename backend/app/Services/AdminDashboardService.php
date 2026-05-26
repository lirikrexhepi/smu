<?php

namespace App\Services;

use App\Models\Academic\Course;
use App\Models\Identity\User;
use Illuminate\Support\Facades\DB;

final class AdminDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function getDashboardData(): array
    {
        // 1. Executive Metrics
        $totalStudents = User::where('role', 'student')->count();
        $totalProfessors = User::where('role', 'professor')->count();
        $totalCourses = Course::count();

        // Calculate average attendance rate
        $attendanceStats = DB::table('course_attendance_records')
            ->selectRaw('SUM(CASE WHEN status IN ("present", "late") THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0) as rate')
            ->first();
        $overallAttendanceRate = $attendanceStats?->rate ?? 100;

        // Calculate campus average GPA
        $gpaStats = DB::table('course_grade_records')
            ->whereNotNull('grade')
            ->avg('grade') ?? 0;

        // 2. Grade Distribution (5 to 10 scale)
        $rawGradeCounts = DB::table('course_grade_records')
            ->whereNotNull('grade')
            ->selectRaw('ROUND(grade) as score, COUNT(*) as count')
            ->groupBy('score')
            ->pluck('count', 'score')
            ->all();

        $gradeDistribution = [];
        $totalGrades = array_sum($rawGradeCounts);
        for ($grade = 5; $grade <= 10; $grade++) {
            $count = $rawGradeCounts[$grade] ?? 0;
            $percentage = $totalGrades > 0 ? round(($count * 100) / $totalGrades) : 0;
            $label = match ($grade) {
                5 => '5 (Fail)',
                6 => '6 (Sufficient)',
                7 => '7 (Satisfactory)',
                8 => '8 (Good)',
                9 => '9 (Very Good)',
                10 => '10 (Excellent)',
                default => (string) $grade,
            };

            $gradeDistribution[] = [
                'grade' => $grade,
                'label' => $label,
                'count' => $count,
                'percentage' => $percentage,
            ];
        }

        // 3. Department Statistics
        $departmentStats = DB::table('departments')
            ->leftJoin('users', function ($join): void {
                $join->on('users.department_id', '=', 'departments.id')
                     ->where('users.role', '=', 'student');
            })
            ->leftJoin('courses', 'courses.department_id', '=', 'departments.id')
            ->select('departments.id', 'departments.name')
            ->selectRaw('COUNT(DISTINCT users.id) as student_count')
            ->selectRaw('COUNT(DISTINCT courses.id) as course_count')
            ->groupBy('departments.id', 'departments.name')
            ->orderBy('student_count', 'desc')
            ->take(5)
            ->get()
            ->map(fn (object $row): array => [
                'id' => (int) $row->id,
                'name' => $row->name,
                'studentCount' => (int) $row->student_count,
                'courseCount' => (int) $row->course_count,
            ])
            ->all();

        // 4. Recent Users
        $recentUsers = User::orderBy('id', 'desc')
            ->take(8)
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'institutionId' => $user->institution_id,
                'timeLabel' => $user->created_at->diffForHumans(),
            ])
            ->all();

        return [
            'metrics' => [
                [
                    'id' => 'm-students',
                    'label' => 'Total Students',
                    'value' => number_format($totalStudents),
                    'helper' => 'Currently active & registered',
                    'tone' => 'green',
                ],
                [
                    'id' => 'm-professors',
                    'label' => 'Faculty Members',
                    'value' => number_format($totalProfessors),
                    'helper' => 'Across all departments',
                    'tone' => 'blue',
                ],
                [
                    'id' => 'm-attendance',
                    'label' => 'Average Attendance',
                    'value' => round($overallAttendanceRate) . '%',
                    'helper' => 'University-wide records',
                    'tone' => 'orange',
                ],
                [
                    'id' => 'm-gpa',
                    'label' => 'Average GPA',
                    'value' => number_format($gpaStats, 2),
                    'helper' => 'Cumulative campus grade',
                    'tone' => 'purple',
                ],
            ],
            'gradeDistribution' => $gradeDistribution,
            'departments' => $departmentStats,
            'recentUsers' => $recentUsers,
        ];
    }
}
