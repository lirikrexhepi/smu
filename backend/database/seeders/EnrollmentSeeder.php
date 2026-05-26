<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        $students = DB::table('students')->get();
        $courses = DB::table('courses')->get();

        if ($students->isEmpty() || $courses->isEmpty()) {
            throw new \RuntimeException('Students and courses must be seeded first.');
        }

        foreach ($students as $student) {
            foreach ($courses as $course) {
                $isCompleted = DB::table('semesters')->where('id', $course->semester_id)->value('code') === 'sem-3';

                $this->updateOrCreateId('student_enrollments', [
                    'student_id' => $student->id,
                    'course_id' => $course->id,
                    'semester_id' => $course->semester_id,
                ], [
                    'status' => $isCompleted ? 'completed' : 'active',
                    'status_label' => $isCompleted ? 'Completed' : 'Active',
                    'enrolled_on' => $isCompleted ? '2025-10-01' : '2026-02-17',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]);
            }
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
