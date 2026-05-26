<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GradeSeeder extends Seeder
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

        $completedGrades = [
            'demo-student-one' => ['ce-3-db' => 9, 'ce-3-os' => 8, 'ce-3-net' => 9, 'ce-3-dsa' => 10, 'ce-3-se' => 8, 'ce-3-stat' => 8],
            'demo-student-two' => ['ce-3-db' => 8, 'ce-3-os' => 7, 'ce-3-net' => 8, 'ce-3-dsa' => 8, 'ce-3-se' => 7, 'ce-3-stat' => 9],
        ];

        $activeScores = [
            'demo-student-one' => ['ce-4-web' => 9, 'ce-4-arch' => 8, 'ce-4-ai' => 9, 'ce-4-mobile' => 9, 'ce-4-sec' => 7, 'ce-4-hci' => 10],
            'demo-student-two' => ['ce-4-web' => 8, 'ce-4-arch' => 7, 'ce-4-ai' => 8, 'ce-4-mobile' => 7, 'ce-4-sec' => 6, 'ce-4-hci' => 9],
        ];

        foreach ($enrollments as $enrollment) {
            $isCompleted = DB::table('semesters')->where('id', $enrollment->semester_id)->value('code') === 'sem-3';
            
            $studentKey = $enrollment->student_key;
            $courseKey = $enrollment->course_key;

            if ($isCompleted) {
                $grade = $completedGrades[$studentKey][$courseKey] ?? ($studentKey === 'demo-student-one' ? 9 : 8);
            } else {
                $grade = $activeScores[$studentKey][$courseKey] ?? ($studentKey === 'demo-student-one' ? 9 : 8);
            }

            $this->seedEnrollmentGrades($enrollment->enrollment_id, $isCompleted, $grade);
        }
    }

    private function seedEnrollmentGrades(int $enrollmentId, bool $isCompleted, int $grade): void
    {
        $records = $isCompleted
            ? [
                ['key' => 'midterm', 'title' => 'Midterm Exam', 'type' => 'Exam', 'grade' => $grade, 'weight' => 30, 'date' => '2025-11-20', 'status' => 'Graded'],
                ['key' => 'assignments', 'title' => 'Assignments Portfolio', 'type' => 'Assignments', 'grade' => $grade, 'weight' => 30, 'date' => '2025-12-16', 'status' => 'Graded'],
                ['key' => 'final', 'title' => 'Final Exam', 'type' => 'Exam', 'grade' => $grade, 'weight' => 40, 'date' => '2026-01-20', 'status' => 'Final'],
            ]
            : [
                ['key' => 'quiz-1', 'title' => 'Quiz 1', 'type' => 'Quiz', 'grade' => $grade, 'weight' => 10, 'date' => '2026-03-18', 'status' => 'Graded'],
                ['key' => 'assignment-1', 'title' => 'Assignment 1', 'type' => 'Assignment', 'grade' => $grade, 'weight' => 15, 'date' => '2026-04-10', 'status' => 'Graded'],
                ['key' => 'midterm', 'title' => 'Midterm Exam', 'type' => 'Exam', 'grade' => $grade, 'weight' => 30, 'date' => '2026-04-29', 'status' => 'Graded'],
            ];

        foreach ($records as $record) {
            $this->updateOrCreateId('course_grade_records', [
                'student_enrollment_id' => $enrollmentId,
                'grade_key' => $record['key'],
            ], [
                'title' => $record['title'],
                'type' => $record['type'],
                'score' => null,
                'grade' => $record['grade'],
                'weight' => $record['weight'],
                'weight_label' => null,
                'graded_on' => $record['date'],
                'date_label' => date('M j, Y', strtotime($record['date'])),
                'status' => $record['status'],
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }
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
