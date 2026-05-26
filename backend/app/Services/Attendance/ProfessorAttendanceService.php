<?php

namespace App\Services\Attendance;

use App\Models\Identity\User;
use App\Models\Gradebook\StudentEnrollment;
use App\Models\Attendance\CourseAttendanceSummary;
use App\Models\Attendance\AttendanceSession;

final class ProfessorAttendanceService
{
    /**
     * @return array<string, mixed>
     */
    public function getAttendanceData(User $user): array
    {
        $professor = $user->professor;
        if ($professor === null) {
            return [];
        }

        $courses = $professor->courses;

        // 1. Retrieve attendance sessions via Eloquent — include the linked schedule for room/type
        $dbSessions = AttendanceSession::with(['course', 'records', 'schedule'])
            ->where('professor_id', $professor->id)
            ->orderByDesc('starts_at')
            ->get();

        $sessionsList = $dbSessions->map(function (AttendanceSession $session): array {
            $present = $session->records->where('status', 'present')->count();
            $late    = $session->records->where('status', 'late')->count();
            $absent  = $session->records->where('status', 'absent')->count();

            $status = $session->closed_at !== null ? 'Recorded' : 'Open';

            // Pull room and type from the linked CourseSchedule when available
            $room = (string) ($session->schedule?->room ?? $session->course?->room ?? 'TBD');
            $type = (string) ($session->schedule?->label ?? 'Lecture');

            return [
                'id'         => (string) $session->id,
                'courseCode' => (string) $session->course?->code,
                'courseName' => (string) $session->course?->name,
                'date'       => date('d M Y', strtotime((string) $session->starts_at)),
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

        // 2. Fetch course-by-course attendance rates
        $coursesList = $courses->map(function (object $course): array {
            $averageAttendance = CourseAttendanceSummary::whereHas(
                'enrollment',
                fn ($q) => $q->where('course_id', $course->id)
            )->avg(\DB::raw('CASE WHEN sessions_held > 0 THEN (sessions_attended * 100.0 / sessions_held) ELSE 100 END')) ?? 100;

            return [
                'id'             => (string) $course->course_key,
                'code'           => (string) $course->code,
                'name'           => (string) $course->name,
                'attendanceRate' => round($averageAttendance),
            ];
        })->values()->all();

        return [
            'sessions' => $sessionsList,
            'courses'  => $coursesList,
        ];
    }
}
