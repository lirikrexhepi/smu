<?php

namespace App\Services\Attendance;

use App\Models\Identity\User;
use App\Models\Gradebook\StudentEnrollment;
use App\Models\Attendance\CourseAttendanceRecord;
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
