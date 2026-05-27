<?php

namespace App\Services;

use App\Models\Identity\User;
use App\Models\Identity\Student;
use App\Models\Identity\Professor;
use App\Models\Identity\StudentEmergencyContact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final readonly class AdminUserService
{
    /**
     * List users with optional searching and filtering.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function listUsers(array $filters): array
    {
        $query = User::with(['faculty', 'department']);

        // Search name/email/ID
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', $search)
                  ->orWhere('email', 'like', $search)
                  ->orWhere('institution_id', 'like', $search);
            });
        }

        // Role filter
        if (!empty($filters['role']) && $filters['role'] !== 'all') {
            $query->where('role', $filters['role']);
        }

        // Department filter
        if (!empty($filters['department_id']) && $filters['department_id'] !== 'all') {
            $query->where('department_id', (int) $filters['department_id']);
        }

        // Faculty filter
        if (!empty($filters['faculty_id']) && $filters['faculty_id'] !== 'all') {
            $query->where('faculty_id', (int) $filters['faculty_id']);
        }

        $users = $query->orderBy('id', 'desc')->paginate(15);

        $mappedItems = collect($users->items())->map(function (User $user): array {
            $details = [];

            if ($user->role === 'student') {
                $student = Student::where('user_id', $user->id)->first();
                $details = [
                    'studentId' => $student?->student_number,
                    'status' => $student?->status ?? 'Active',
                    'yearOfStudy' => $student?->year_of_study,
                ];
            } elseif ($user->role === 'professor') {
                $prof = Professor::where('user_id', $user->id)->first();
                $details = [
                    'title' => $prof?->title,
                    'office' => $prof?->office,
                ];
            }

            return array_merge([
                'id' => $user->id,
                'publicId' => $user->public_id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'institutionId' => $user->institution_id,
                'faculty' => $user->faculty?->name,
                'department' => $user->department?->name,
                'avatarUrl' => $user->avatar_url,
                'createdAt' => $user->created_at->toIso8601String(),
            ], $details);
        })->all();

        return [
            'items' => $mappedItems,
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ]
        ];
    }

    /**
     * Get detail of a specific user.
     */
    public function getUser(int $id): array
    {
        $user = User::with(['faculty', 'department'])->findOrFail($id);
        $result = [
            'id' => $user->id,
            'publicId' => $user->public_id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'institutionId' => $user->institution_id,
            'facultyId' => $user->faculty_id,
            'departmentId' => $user->department_id,
            'avatarUrl' => $user->avatar_url,
            'createdAt' => $user->created_at->toIso8601String(),
        ];

        if ($user->role === 'student') {
            $student = Student::where('user_id', $user->id)->first();
            if ($student) {
                $contact = StudentEmergencyContact::where('student_id', $student->id)
                    ->where('is_primary', true)
                    ->first();

                $result = array_merge($result, [
                    'studentNumber' => $student->student_number,
                    'programId' => $student->program_id,
                    'status' => $student->status,
                    'yearOfStudy' => $student->year_of_study,
                    'phone' => $student->phone,
                    'address' => $student->address,
                    'dateOfBirth' => $student->date_of_birth,
                    'gender' => $student->gender,
                    'nationality' => $student->nationality,
                    'personalNumber' => $student->personal_number,
                    'emergencyContactName' => $contact?->name,
                    'emergencyContactRelationship' => $contact?->relationship,
                    'emergencyContactPhone' => $contact?->phone,
                ]);
            }
        } elseif ($user->role === 'professor') {
            $prof = Professor::where('user_id', $user->id)->first();
            if ($prof) {
                $result = array_merge($result, [
                    'title' => $prof->title,
                    'office' => $prof->office,
                    'officeHours' => $prof->office_hours,
                    'consultation' => $prof->consultation,
                ]);
            }
        }

        return $result;
    }

    /**
     * Create a user transactionally.
     */
    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $role = $data['role'];

            // 1. Auto generate institution_id
            $prefix = match($role) {
                'student' => 'STU',
                'professor' => 'PROF',
                'admin' => 'ADM',
            };
            
            $existingIds = User::where('role', $role)
                ->where('institution_id', 'like', "$prefix-%")
                ->pluck('institution_id')
                ->all();

            $maxNum = 1000;
            foreach ($existingIds as $existingId) {
                preg_match('/\d+/', $existingId, $matches);
                if (isset($matches[0])) {
                    $num = (int) $matches[0];
                    if ($num > $maxNum) {
                        $maxNum = $num;
                    }
                }
            }
            $nextNum = $maxNum + 1;
            $institutionId = "$prefix-$nextNum";


            // 2. Create User
            $user = User::create([
                'public_id' => $prefix . '-' . Str::random(10),
                'role' => $role,
                'institution_id' => $institutionId,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'faculty_id' => $data['faculty_id'] ?? null,
                'department_id' => $data['department_id'] ?? null,
            ]);

            // 3. Create role-specific dependent records
            if ($role === 'student') {
                $student = Student::create([
                    'user_id' => $user->id,
                    'program_id' => $data['program_id'] ?? null,
                    'student_number' => $institutionId,
                    'student_key' => Str::slug($data['name']) . '-' . rand(1000, 9999),
                    'status' => 'Active',
                    'status_label' => 'Active',
                    'year_of_study' => $data['year_of_study'] ?? '1st Year',
                    'phone' => $data['phone'] ?? null,
                    'address' => $data['address'] ?? null,
                    'date_of_birth' => $data['date_of_birth'] ?? null,
                    'gender' => $data['gender'] ?? null,
                    'nationality' => $data['nationality'] ?? null,
                    'personal_number' => $data['personal_number'] ?? null,
                    'profile_updated_at' => now(),
                ]);

                if (!empty($data['emergency_contact_name'])) {
                    StudentEmergencyContact::create([
                        'student_id' => $student->id,
                        'name' => $data['emergency_contact_name'],
                        'relationship' => $data['emergency_contact_relationship'] ?? 'Parent',
                        'phone' => $data['emergency_contact_phone'] ?? '',
                        'is_primary' => true,
                    ]);
                }
            } elseif ($role === 'professor') {
                Professor::create([
                    'user_id' => $user->id,
                    'title' => $data['title'] ?? 'Assistant Professor',
                    'office' => $data['office'] ?? null,
                    'office_hours' => $data['office_hours'] ?? null,
                    'consultation' => $data['consultation'] ?? null,
                ]);
            }

            return $user;
        });
    }

    /**
     * Update an existing user.
     */
    public function updateUser(int $id, array $data): User
    {
        return DB::transaction(function () use ($id, $data): User {
            $user = User::findOrFail($id);

            // Update user core fields
            $userUpdate = [
                'name' => $data['name'],
                'email' => $data['email'],
                'faculty_id' => $data['faculty_id'] ?? null,
                'department_id' => $data['department_id'] ?? null,
            ];

            if (!empty($data['password'])) {
                $userUpdate['password'] = Hash::make($data['password']);
            }

            $user->update($userUpdate);

            // Update dependent fields
            if ($user->role === 'student') {
                $student = Student::where('user_id', $user->id)->first();
                if ($student) {
                    $student->update([
                        'program_id' => $data['program_id'] ?? $student->program_id,
                        'year_of_study' => $data['year_of_study'] ?? $student->year_of_study,
                        'phone' => $data['phone'] ?? $student->phone,
                        'address' => $data['address'] ?? $student->address,
                        'date_of_birth' => $data['date_of_birth'] ?? $student->date_of_birth,
                        'gender' => $data['gender'] ?? $student->gender,
                        'nationality' => $data['nationality'] ?? $student->nationality,
                        'personal_number' => $data['personal_number'] ?? $student->personal_number,
                        'profile_updated_at' => now(),
                    ]);

                    if (!empty($data['emergency_contact_name'])) {
                        StudentEmergencyContact::updateOrCreate(
                            ['student_id' => $student->id, 'is_primary' => true],
                            [
                                'name' => $data['emergency_contact_name'],
                                'relationship' => $data['emergency_contact_relationship'] ?? 'Parent',
                                'phone' => $data['emergency_contact_phone'] ?? '',
                            ]
                        );
                    }
                }
            } elseif ($user->role === 'professor') {
                Professor::where('user_id', $user->id)
                    ->update([
                        'title' => $data['title'] ?? 'Assistant Professor',
                        'office' => $data['office'] ?? null,
                        'office_hours' => $data['office_hours'] ?? null,
                        'consultation' => $data['consultation'] ?? null,
                    ]);
            }

            return $user;
        });
    }

    /**
     * Delete a user securely.
     */
    public function deleteUser(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $user = User::findOrFail($id);
            $userRole = $user->role;

            // Cascade delete will delete dependent elements, but let's clear them explicitly to be safe
            if ($userRole === 'student') {
                $student = Student::where('user_id', $user->id)->first();
                if ($student) {
                    $student->emergencyContacts()->delete();
                    $student->delete();
                }
            } elseif ($userRole === 'professor') {
                Professor::where('user_id', $user->id)->delete();
            }

            $user->delete();
        });
    }
}
