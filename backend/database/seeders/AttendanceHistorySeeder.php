<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendanceHistorySeeder extends Seeder
{
    public function run(): void
    {
        $enrollments = DB::table('student_enrollments')
            ->join('students', 'student_enrollments.student_id', '=', 'students.id')
            ->join('courses', 'student_enrollments.course_id', '=', 'courses.id')
            ->select('student_enrollments.id as enrollment_id', 'students.student_key', 'courses.course_key', 'courses.semester_id')
            ->get();

        if ($enrollments->isEmpty()) {
            throw new \RuntimeException('Student enrollments must be seeded first.');
        }

        foreach ($enrollments as $enrollment) {
            $isCompleted = DB::table('semesters')->where('id', $enrollment->semester_id)->value('code') === 'sem-3';
            $attendance = $this->attendanceRateFor($enrollment->student_key, $enrollment->course_key);

            $this->seedCourseAttendance($enrollment->enrollment_id, $isCompleted, $attendance);
        }

        // Seed attendance history pages for both students
        $students = DB::table('students')->get();
        foreach ($students as $student) {
            $this->seedAttendancePage($student);
        }
    }

    private function seedCourseAttendance(int $enrollmentId, bool $isCompleted, int $attendance): void
    {
        $sessionsHeld = 100;
        $sessionsAttended = (int) round($sessionsHeld * $attendance / 100);
        $lateRecords = min(3, max(0, $sessionsAttended - 1), $attendance < 90 ? 2 : 0);
        $presentRecords = $sessionsAttended - $lateRecords;
        $baseDate = $isCompleted ? '2025-10-13' : '2026-02-15';

        foreach (range(0, $sessionsHeld - 1) as $index) {
            $date = date('Y-m-d', strtotime($baseDate.' +'.$index.' days'));
            $status = match (true) {
                $index < $presentRecords => 'present',
                $index < $sessionsAttended => 'late',
                default => 'absent',
            };

            $this->updateOrCreateId('course_attendance_records', [
                'student_enrollment_id' => $enrollmentId,
                'record_key' => 'demo-'.$index,
            ], [
                'held_on' => $date,
                'date_label' => date('M j, Y', strtotime($date)),
                'type' => 'Lecture',
                'status' => $status,
                'status_label' => ucfirst($status),
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }
    }

    private function seedAttendancePage(\stdClass $student): void
    {
        $activeCourses = DB::table('courses')
            ->join('semesters', 'courses.semester_id', '=', 'semesters.id')
            ->join('course_schedules', 'courses.id', '=', 'course_schedules.course_id')
            ->where('semesters.code', 'sem-4')
            ->select('courses.id', 'course_schedules.starts_at')
            ->orderBy('courses.id')
            ->get();

        if ($activeCourses->isEmpty()) {
            return;
        }

        $historyStatuses = ['present', 'late', 'present', 'absent', 'present', 'present', 'late', 'present', 'absent', 'present', 'present', 'present'];
        $historyDates = ['2026-03-02', '2026-03-04', '2026-03-09', '2026-03-12', '2026-03-16', '2026-03-19', '2026-03-23', '2026-03-26', '2026-04-02', '2026-04-09', '2026-04-16', '2026-04-23'];

        foreach ($historyStatuses as $index => $status) {
            $course = $activeCourses[$index % count($activeCourses)];
            $date = $historyDates[$index];

            $this->updateOrCreateId('attendance_history_records', [
                'student_id' => $student->id,
                'course_id' => $course->id,
                'record_key' => 'demo-'.$student->student_key.'-'.$index,
            ], [
                'recorded_on' => $date,
                'date_label' => date('M j, Y', strtotime($date)),
                'time_label' => substr($course->starts_at, 0, 5),
                'type' => 'Lecture',
                'professor_name' => 'Dr. Arben Krasniqi',
                'result' => $status,
                'result_label' => ucfirst($status),
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }
    }

    private function attendanceRateFor(string $studentKey, string $courseKey): int
    {
        $rates = [
            'demo-student-one' => ['ce-4-web' => 92, 'ce-4-arch' => 88, 'ce-4-ai' => 90, 'ce-4-mobile' => 85, 'ce-4-sec' => 78, 'ce-4-hci' => 95],
            'demo-student-two' => ['ce-4-web' => 85, 'ce-4-arch' => 80, 'ce-4-ai' => 82, 'ce-4-mobile' => 76, 'ce-4-sec' => 70, 'ce-4-hci' => 88],
        ];

        if (isset($rates[$studentKey][$courseKey])) {
            return $rates[$studentKey][$courseKey];
        }

        return $studentKey === 'demo-student-one' ? 94 : 88;
    }

    private function updateOrCreateId(string $table, array $where, array $values): int
    {
        $existing = DB::table($table)->where($where)->first();

        if ($existing !== null) {
            DB::table($table)->where('id', $existing->id)->update(array_diff_key($values, array_flip(['created_at'])));
            return (int) $existing->id;
        }

        return (int) DB::table($table)->insertGetId(array_merge($where, $values));
    }
}
