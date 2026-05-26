<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InstitutionSeeder extends Seeder
{
    private const FACULTY_NAME = 'Faculty of Electrical and Computer Engineering';
    private const DEPARTMENT_NAME = 'Computer Engineering';
    private const PROGRAM_NAME = 'Bachelor of Computer Engineering';
    private const ACADEMIC_YEAR = '2025/2026';
    private const REQUIRED_CREDITS = 180;

    public function run(): void
    {
        $now = now();

        $facultyId = $this->updateOrCreateId('faculties', ['name' => self::FACULTY_NAME], [
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $departmentId = $this->updateOrCreateId('departments', [
            'faculty_id' => $facultyId,
            'name' => self::DEPARTMENT_NAME,
        ], [
            'updated_at' => $now,
            'created_at' => $now,
        ]);

        $programId = $this->updateOrCreateId('programs', [
            'department_id' => $departmentId,
            'name' => self::PROGRAM_NAME,
        ], [
            'degree_level' => 'Bachelor',
            'required_credits' => self::REQUIRED_CREDITS,
            'updated_at' => $now,
            'created_at' => $now,
        ]);

        $academicYearId = $this->updateOrCreateId('academic_years', ['name' => self::ACADEMIC_YEAR], [
            'starts_on' => '2025-10-01',
            'ends_on' => '2026-07-15',
            'is_current' => true,
            'updated_at' => $now,
            'created_at' => $now,
        ]);

        $this->updateOrCreateId('semesters', ['code' => 'sem-3'], [
            'academic_year_id' => $academicYearId,
            'name' => '3rd Semester',
            'number' => 3,
            'starts_on' => '2025-10-01',
            'ends_on' => '2026-02-10',
            'is_current' => false,
            'updated_at' => $now,
            'created_at' => $now,
        ]);

        $this->updateOrCreateId('semesters', ['code' => 'sem-4'], [
            'academic_year_id' => $academicYearId,
            'name' => '4th Semester',
            'number' => 4,
            'starts_on' => '2026-02-17',
            'ends_on' => '2026-06-26',
            'is_current' => true,
            'updated_at' => $now,
            'created_at' => $now,
        ]);
    }

    private function updateOrCreateId(string $table, array $where, array $values): int
    {
        $existing = DB::table($table)->where($where)->first();

        if ($existing !== null) {
            DB::table($table)->where($where)->update($values);
            return (int) $existing->id;
        }

        return (int) DB::table($table)->insertGetId(array_merge($where, $values));
    }
}
