<?php

namespace App\Services\Academic;

use App\Models\Identity\User;
use App\Models\Attendance\CourseAttendanceRecord;
use App\Models\Gradebook\CourseGradeRecord;
use App\Models\Gradebook\StudentEnrollment;

final class ProfessorCoursesService
{
    // Colour cycle — applied by position, not by hardcoded course code
    private const TONES = ['blue', 'green', 'purple', 'orange', 'teal', 'red'];

    /**
     * @return array<string, mixed>
     */
    public function getCourses(User $user): array
    {
        $professor = $user->professor;
        if ($professor === null) {
            return [];
        }

        $courses = $professor->courses()->with(['schedules', 'enrollments', 'semester'])->get();

        $courseList = $courses->values()->map(function (object $course, int $index): array {
            $studentCount = StudentEnrollment::where('course_id', $course->id)
                ->where('status', 'active')
                ->count();

            $attendanceStats = CourseAttendanceRecord::whereHas(
                'enrollment',
                fn ($q) => $q->where('course_id', $course->id)
            )->whereIn('status', ['present', 'absent', 'late', 'recorded'])
            ->selectRaw('COUNT(*) as total_sessions')
            ->selectRaw("SUM(CASE WHEN status in ('present', 'late', 'recorded') THEN 1 ELSE 0 END) as sessions_attended")
            ->first();

            $averageAttendance = 100;
            if ($attendanceStats && $attendanceStats->total_sessions > 0) {
                $averageAttendance = ($attendanceStats->sessions_attended * 100.0) / $attendanceStats->total_sessions;
            }

            $averageGrade = CourseGradeRecord::whereHas(
                'enrollment',
                fn ($q) => $q->where('course_id', $course->id)
            )->whereNotNull('grade')->avg('grade') ?? 0;

            // Pending: grade records with no grade yet (grade IS NULL)
            $pendingGrades = CourseGradeRecord::whereHas(
                'enrollment',
                fn ($q) => $q->where('course_id', $course->id)
            )->whereNull('grade')->count();

            // Derive semester name from the course's linked semester
            $semesterName = $course->semester?->name ?? 'Current Semester';

            // Derive status from the course model's own status field
            $courseStatus = match ((string) ($course->status ?? 'active')) {
                'active'   => 'Active',
                'exam'     => 'Exam Week',
                'closing'  => 'Closing',
                default    => 'Active',
            };

            $schedule   = $course->schedules->first();
            $scheduleLabel = $schedule !== null
                ? ($schedule->days_label . ' ' . $schedule->time_label)
                : 'TBD';

            // Tone by position in list (cycles through colours)
            $tone = self::TONES[$index % count(self::TONES)];

            return [
                'id'             => (string) $course->course_key,
                'code'           => (string) $course->code,
                'name'           => (string) $course->name,
                'semester'       => $semesterName,
                'room'           => (string) ($course->room ?? 'TBD'),
                'schedule'       => $scheduleLabel,
                'students'       => $studentCount,
                'attendanceRate' => round($averageAttendance),
                'averageGrade'   => round($averageGrade, 1),
                'pendingGrades'  => $pendingGrades,
                'status'         => $courseStatus,
                'tone'           => $tone,
            ];
        })->all();

        return [
            'courses' => $courseList,
        ];
    }
}
