<?php

namespace App\Services\Dashboard;

use App\Models\Identity\User;
use App\Models\Gradebook\StudentEnrollment;
use App\Models\Attendance\CourseAttendanceSummary;
use App\Models\Gradebook\CourseGradeRecord;
use App\Models\Academic\CourseSchedule;
use App\Models\Academic\CourseEvent;
use App\Models\Attendance\AttendanceSession;
use Illuminate\Support\Facades\DB;

final class ProfessorDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function getDashboardData(User $user): array
    {
        $professor = $user->professor;
        if ($professor === null) {
            return [];
        }

        $courses   = $professor->courses;
        $courseIds = $courses->pluck('id')->all();

        // ── Metrics ─────────────────────────────────────────────────────────
        $activeCoursesCount = $courses->count();

        $totalStudents = StudentEnrollment::whereIn('course_id', $courseIds)
            ->where('status', 'active')
            ->count();

        $averageAttendance = CourseAttendanceSummary::whereHas(
            'enrollment',
            fn ($q) => $q->whereIn('course_id', $courseIds)
        )->avg(DB::raw('CASE WHEN sessions_held > 0 THEN (sessions_attended * 100.0 / sessions_held) ELSE 100 END')) ?? 0;

        // Pending grades = grade records that still have no grade value
        $pendingGrades = CourseGradeRecord::whereHas(
            'enrollment',
            fn ($q) => $q->whereIn('course_id', $courseIds)
        )->whereNull('grade')->count();

        // ── Today's Teaching Schedule ────────────────────────────────────────
        // Build from real AttendanceSessions (open/recorded today) first,
        // then fall back to CourseSchedule entries for scheduled slots
        $dayOfWeek = date('l');
        $todayStr  = date('Y-m-d');

        // Real sessions started today
        $todaySessions = AttendanceSession::with(['course', 'records', 'schedule'])
            ->where('professor_id', $professor->id)
            ->whereDate('starts_at', $todayStr)
            ->orderBy('starts_at')
            ->get()
            ->map(function (AttendanceSession $session): array {
                $present = $session->records->where('status', 'present')->count();
                $absent  = $session->records->where('status', 'absent')->count();
                $late    = $session->records->where('status', 'late')->count();
                $status  = $session->closed_at !== null ? 'Recorded' : 'Open';
                $room    = (string) ($session->schedule?->room ?? $session->course?->room ?? 'TBD');
                $type    = (string) ($session->schedule?->label ?? 'Lecture');

                return [
                    'id'         => 's-' . $session->id,
                    'courseCode' => (string) $session->course?->code,
                    'courseName' => (string) $session->course?->name,
                    'date'       => date('d M Y'),
                    'time'       => date('H:i', strtotime((string) $session->starts_at))
                        . '-' . date('H:i', strtotime((string) $session->ends_at)),
                    'room'       => $room,
                    'type'       => $type,
                    'present'    => $present,
                    'absent'     => $absent,
                    'late'       => $late,
                    'status'     => $status,
                ];
            })->values()->all();

        // If no real sessions today, show scheduled slots from CourseSchedule
        if (count($todaySessions) === 0) {
            $schedules = CourseSchedule::with('course')
                ->whereIn('course_id', $courseIds)
                ->get()
                ->filter(function (CourseSchedule $schedule) use ($dayOfWeek): bool {
                    $days = $schedule->days;
                    return is_array($days) && in_array($dayOfWeek, $days, true);
                });

            // If nothing matches today's day, just show up to 3 schedules as upcoming
            if ($schedules->isEmpty()) {
                $schedules = CourseSchedule::with('course')
                    ->whereIn('course_id', $courseIds)
                    ->orderBy('starts_at')
                    ->take(3)
                    ->get();
            }

            $todaySessions = $schedules->values()->map(function (CourseSchedule $schedule): array {
                return [
                    'id'         => 'sched-' . $schedule->id,
                    'courseCode' => (string) $schedule->course?->code,
                    'courseName' => (string) $schedule->course?->name,
                    'date'       => date('d M Y'),
                    'time'       => substr((string) $schedule->starts_at, 0, 5)
                        . '-' . substr((string) $schedule->ends_at, 0, 5),
                    'room'       => (string) ($schedule->room ?? 'TBD'),
                    'type'       => (string) ($schedule->label ?? 'Lecture'),
                    'present'    => 0,
                    'absent'     => 0,
                    'late'       => 0,
                    'status'     => 'Scheduled',
                ];
            })->all();
        }

        // ── Assessment Queue ─────────────────────────────────────────────────
        $assessments = CourseEvent::with('course')
            ->whereIn('course_id', $courseIds)
            ->where('category', 'deadline')
            ->orderBy('event_date')
            ->get()
            ->map(function (CourseEvent $event) use ($courseIds): array {
                $total = DB::table('student_enrollments')
                    ->where('course_id', $event->course_id)
                    ->whereIn('status', ['active', 'registered', 'upcoming', 'completed'])
                    ->count();

                $graded = DB::table('course_grade_records')
                    ->join('student_enrollments', 'course_grade_records.student_enrollment_id', '=', 'student_enrollments.id')
                    ->where('student_enrollments.course_id', $event->course_id)
                    ->where('course_grade_records.grade_key', $event->event_key)
                    ->whereNotNull('course_grade_records.grade')
                    ->count();

                $submitted = DB::table('course_grade_records')
                    ->join('student_enrollments', 'course_grade_records.student_enrollment_id', '=', 'student_enrollments.id')
                    ->where('student_enrollments.course_id', $event->course_id)
                    ->where('course_grade_records.grade_key', $event->event_key)
                    ->count();

                return [
                    'id'        => 'a-' . $event->id,
                    'courseCode'=> (string) $event->course?->code,
                    'title'     => (string) $event->title,
                    'type'      => (string) $event->type,
                    'dueDate'   => date('d M Y', strtotime((string) $event->event_date)),
                    'submitted' => $submitted,
                    'total'     => $total > 0 ? $total : 0,
                    'graded'    => $graded,
                ];
            })
            ->all();

        // ── Courses Summary ──────────────────────────────────────────────────
        $coursesList = $courses->values()->map(function (object $course): array {
            $studentCount = StudentEnrollment::where('course_id', $course->id)
                ->where('status', 'active')
                ->count();

            $avgAttendance = CourseAttendanceSummary::whereHas(
                'enrollment',
                fn ($q) => $q->where('course_id', $course->id)
            )->avg(DB::raw('CASE WHEN sessions_held > 0 THEN (sessions_attended * 100.0 / sessions_held) ELSE 100 END')) ?? 0;

            $avgGrade = CourseGradeRecord::whereHas(
                'enrollment',
                fn ($q) => $q->where('course_id', $course->id)
            )->whereNotNull('grade')->avg('grade') ?? 0;

            $courseStatus = match ((string) ($course->status ?? 'active')) {
                'active'  => 'Active',
                'exam'    => 'Exam Week',
                'closing' => 'Closing',
                default   => 'Active',
            };

            return [
                'id'             => (string) $course->course_key,
                'code'           => (string) $course->code,
                'name'           => (string) $course->name,
                'students'       => $studentCount,
                'averageGrade'   => round($avgGrade, 1),
                'attendanceRate' => round($avgAttendance),
                'status'         => $courseStatus,
            ];
        })->all();

        return [
            'metrics' => [
                ['label' => 'Active Courses',  'value' => (string) $activeCoursesCount,       'helper' => 'Assigned this semester', 'tone' => 'blue'],
                ['label' => 'Students',         'value' => (string) $totalStudents,             'helper' => 'Across all sections',    'tone' => 'green'],
                ['label' => 'Attendance',       'value' => round($averageAttendance) . '%',     'helper' => 'Average this semester',  'tone' => 'orange'],
                ['label' => 'Pending Grades',   'value' => (string) $pendingGrades,             'helper' => 'Needs review',           'tone' => 'purple'],
            ],
            'sessions'    => $todaySessions,
            'assessments' => $assessments,
            'courses'     => $coursesList,
        ];
    }
}
