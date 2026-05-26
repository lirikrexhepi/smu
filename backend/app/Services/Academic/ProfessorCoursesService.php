<?php

namespace App\Services\Academic;

use App\Models\Identity\User;
use App\Models\Attendance\CourseAttendanceSummary;
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

        $courses = $professor->courses()->with(['schedules', 'enrollments.attendanceSummary'])->get();

        $courseList = $courses->values()->map(function (object $course, int $index): array {
            $studentCount = StudentEnrollment::where('course_id', $course->id)
                ->where('status', 'active')
                ->count();

            $averageAttendance = CourseAttendanceSummary::whereHas(
                'enrollment',
                fn ($q) => $q->where('course_id', $course->id)
            )->avg(\DB::raw('CASE WHEN sessions_held > 0 THEN (sessions_attended * 100.0 / sessions_held) ELSE 100 END')) ?? 0;

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
            $semesterName = \Illuminate\Support\Facades\DB::table('semesters')
                ->where('id', $course->semester_id)
                ->value('name') ?? 'Current Semester';

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
