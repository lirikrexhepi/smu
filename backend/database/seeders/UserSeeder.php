<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $facultyId = DB::table('faculties')->where('name', 'Faculty of Electrical and Computer Engineering')->value('id');
        $departmentId = DB::table('departments')->where('name', 'Computer Engineering')->value('id');
        $programId = DB::table('programs')->where('name', 'Bachelor of Computer Engineering')->value('id');

        if (!$facultyId || !$departmentId || !$programId) {
            throw new \RuntimeException('Institution structure must be seeded first.');
        }

        $this->seedProfessors($facultyId, $departmentId);
        $this->seedAdmin($facultyId, $departmentId);
        $this->seedStudents($facultyId, $departmentId, $programId);
    }

    private function seedProfessors(int $facultyId, int $departmentId): void
    {
        $rows = [
            [
                'public_id' => 'prof-demo-ce',
                'institution_id' => 'PROF-1001',
                'name' => 'Dr. Arben Krasniqi',
                'email' => 'arben.krasniqi@example.com',
                'title' => 'Associate Professor',
                'office' => 'B-301',
                'office_hours' => 'Tue 13:00 - 15:00',
                'consultation' => 'By appointment',
            ],
            [
                'public_id' => 'prof-demo-elira',
                'institution_id' => 'PROF-1002',
                'name' => 'Dr. Elira Dervishi',
                'email' => 'elira.dervishi@example.com',
                'title' => 'Assistant Professor',
                'office' => 'C-214',
                'office_hours' => 'Thu 13:00 - 15:00',
                'consultation' => 'Email for appointment',
            ],
        ];

        foreach ($rows as $row) {
            $userId = $this->updateOrCreateId('users', ['public_id' => $row['public_id']], [
                'role' => 'professor',
                'institution_id' => $row['institution_id'],
                'name' => $row['name'],
                'email' => $row['email'],
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'faculty_id' => $facultyId,
                'department_id' => $departmentId,
                'avatar_url' => null,
                'remember_token' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]);

            $this->updateOrCreateId('professors', ['user_id' => $userId], [
                'title' => $row['title'],
                'office' => $row['office'],
                'office_hours' => $row['office_hours'],
                'consultation' => $row['consultation'],
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }
    }

    private function seedAdmin(int $facultyId, int $departmentId): void
    {
        $this->updateOrCreateId('users', ['public_id' => 'admin-demo-1001'], [
            'role' => 'admin',
            'institution_id' => 'ADM-1001',
            'name' => 'Demo Admin',
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'faculty_id' => $facultyId,
            'department_id' => $departmentId,
            'avatar_url' => null,
            'remember_token' => null,
            'updated_at' => now(),
            'created_at' => now(),
        ]);
    }

    private function seedStudents(int $facultyId, int $departmentId, int $programId): void
    {
        $rows = [
            [
                'public_id' => 'stu-demo-1001',
                'institution_id' => 'STU-1001',
                'student_number' => 'STU-1001',
                'student_key' => 'demo-student-one',
                'name' => 'Demo Student One',
                'email' => 'student1@example.com',
                'phone' => '+383 44 100 101',
                'address' => 'Rr. B, Prishtina',
                'date_of_birth' => '2004-04-12',
                'gender' => 'Female',
                'nationality' => 'Kosovar',
                'personal_number' => 'DEMO1001',
            ],
            [
                'public_id' => 'stu-demo-1002',
                'institution_id' => 'STU-1002',
                'student_number' => 'STU-1002',
                'student_key' => 'demo-student-two',
                'name' => 'Demo Student Two',
                'email' => 'student2@example.com',
                'phone' => '+383 44 100 102',
                'address' => 'Rr. C, Prishtina',
                'date_of_birth' => '2003-09-05',
                'gender' => 'Male',
                'nationality' => 'Kosovar',
                'personal_number' => 'DEMO1002',
            ],
        ];

        foreach ($rows as $row) {
            $userId = $this->updateOrCreateId('users', ['public_id' => $row['public_id']], [
                'role' => 'student',
                'institution_id' => $row['institution_id'],
                'name' => $row['name'],
                'email' => $row['email'],
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'faculty_id' => $facultyId,
                'department_id' => $departmentId,
                'avatar_url' => null,
                'remember_token' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]);

            $studentId = $this->updateOrCreateId('students', ['student_key' => $row['student_key']], [
                'user_id' => $userId,
                'program_id' => $programId,
                'student_number' => $row['student_number'],
                'status' => 'Active',
                'status_label' => 'Active',
                'year_of_study' => '2nd Year',
                'phone' => $row['phone'],
                'address' => $row['address'],
                'date_of_birth' => $row['date_of_birth'],
                'gender' => $row['gender'],
                'nationality' => $row['nationality'],
                'personal_number' => $row['personal_number'],
                'profile_updated_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]);

            $this->updateOrCreateId('student_emergency_contacts', [
                'student_id' => $studentId,
                'is_primary' => true,
            ], [
                'name' => 'Demo Parent',
                'relationship' => 'Parent',
                'phone' => '+383 44 200 200',
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }
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
