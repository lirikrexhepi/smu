<?php

namespace App\Http\Controllers\Profile;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class StudentProfileController
{
    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success($this->profileData($request));
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return ApiResponse::error('Unauthenticated.', status: 401);
        }

        $student = DB::table('students')->where('user_id', $user->id)->first();
        if ($student === null) {
            return ApiResponse::error('Student profile not found.', status: 404);
        }

        DB::transaction(function () use ($request, $user, $student): void {
            // Update User fields
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'name' => (string) $request->input('fullName', $user->name),
                    'email' => (string) $request->input('email', $user->email),
                    'updated_at' => now(),
                ]);

            // Update Student fields
            DB::table('students')
                ->where('id', $student->id)
                ->update([
                    'phone' => (string) $request->input('phone', ''),
                    'address' => (string) $request->input('address', ''),
                    'date_of_birth' => (string) $request->input('dateOfBirth', ''),
                    'gender' => (string) $request->input('gender', ''),
                    'nationality' => (string) $request->input('nationality', ''),
                    'personal_number' => (string) $request->input('personalNumber', ''),
                    'profile_updated_at' => now(),
                    'updated_at' => now(),
                ]);

            // Update/Create primary emergency contact
            $existingContact = DB::table('student_emergency_contacts')
                ->where('student_id', $student->id)
                ->where('is_primary', true)
                ->first();

            if ($existingContact !== null) {
                DB::table('student_emergency_contacts')
                    ->where('id', $existingContact->id)
                    ->update([
                        'name' => (string) $request->input('emergencyContactName', ''),
                        'relationship' => (string) $request->input('emergencyContactRelationship', ''),
                        'phone' => (string) $request->input('emergencyContactPhone', ''),
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('student_emergency_contacts')->insert([
                    'student_id' => $student->id,
                    'is_primary' => true,
                    'name' => (string) $request->input('emergencyContactName', ''),
                    'relationship' => (string) $request->input('emergencyContactRelationship', ''),
                    'phone' => (string) $request->input('emergencyContactPhone', ''),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return ApiResponse::success($this->profileData($request), 'Student profile updated successfully.');
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return ApiResponse::error('Unauthenticated.', status: 401);
        }

        if (! $request->hasFile('avatar')) {
            return ApiResponse::error('No avatar file uploaded.', status: 400);
        }

        $file = $request->file('avatar');
        if ($file === null || ! $file->isValid()) {
            return ApiResponse::error('Invalid avatar file.', status: 400);
        }

        $path = $file->store('avatars', 'uploads');

        if ($path === false) {
            return ApiResponse::error('Failed to store avatar.', status: 500);
        }

        $url = '/uploads/' . $path;
        DB::table('users')
            ->where('id', $user->id)
            ->update([
                'avatar_url' => $url,
                'updated_at' => now(),
            ]);

        return ApiResponse::success($this->profileData($request), 'Student avatar uploaded successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function profileData(Request $request): array
    {
        $user = $request->user();
        $student = $user === null ? null : DB::table('students')->where('user_id', $user->id)->first();
        $contact = $student === null ? null : DB::table('student_emergency_contacts')
            ->where('student_id', $student->id)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->first();

        $fullName = (string) ($user?->name ?? '');

        $programName = '';
        $semesterLabel = '';
        $academicYearLabel = '';
        $summary = [
            'averageGrade' => '0',
            'creditsEarned' => '0',
            'creditsRequired' => '0',
            'academicStanding' => '',
        ];

        if ($student !== null) {
            if ($student->program_id !== null) {
                $programName = DB::table('programs')->where('id', $student->program_id)->value('name') ?? '';
            }

            $semester = DB::table('student_enrollments')
                ->join('semesters', 'semesters.id', '=', 'student_enrollments.semester_id')
                ->leftJoin('academic_years', 'academic_years.id', '=', 'semesters.academic_year_id')
                ->where('student_enrollments.student_id', $student->id)
                ->where('student_enrollments.status', '!=', 'dropped')
                ->select('semesters.name as semester_name', 'academic_years.name as academic_year_name')
                ->orderByDesc('semesters.is_current')
                ->orderByDesc('semesters.number')
                ->orderByDesc('semesters.id')
                ->first();

            $semesterLabel = $semester?->semester_name ?? '';
            $academicYearLabel = $semester?->academic_year_name ?? '';

            $summary = $this->calculateAcademicSummary((int) $student->id);
        }

        return [
            'studentId' => (string) ($student?->student_number ?? $user?->institution_id ?? ''),
            'fullName' => $fullName,
            'initials' => $this->initials($fullName),
            'avatarUrl' => $user?->avatar_url,
            'status' => (string) ($student?->status ?? ''),
            'studentStatusLabel' => (string) ($student?->status_label ?? $student?->status ?? ''),
            'faculty' => (string) ($user?->faculty?->name ?? ''),
            'department' => (string) ($user?->department?->name ?? ''),
            'program' => $programName,
            'yearOfStudy' => (string) ($student?->year_of_study ?? ''),
            'semester' => $semesterLabel,
            'academicYear' => $academicYearLabel,
            'currentGpa' => $summary['averageGrade'],
            'creditsEarned' => $summary['creditsEarned'],
            'creditsRequired' => $summary['creditsRequired'],
            'academicStanding' => $summary['academicStanding'],
            'email' => (string) ($user?->email ?? ''),
            'phone' => (string) ($student?->phone ?? ''),
            'address' => (string) ($student?->address ?? ''),
            'dateOfBirth' => (string) ($student?->date_of_birth ?? ''),
            'gender' => (string) ($student?->gender ?? ''),
            'nationality' => (string) ($student?->nationality ?? ''),
            'personalNumber' => (string) ($student?->personal_number ?? ''),
            'emergencyContact' => [
                'name' => (string) ($contact?->name ?? ''),
                'relationship' => (string) ($contact?->relationship ?? ''),
                'phone' => (string) ($contact?->phone ?? ''),
            ],
            'updatedAt' => $student?->profile_updated_at,
        ];
    }

    private function calculateAcademicSummary(int $studentId): array
    {
        $gradeStats = DB::table('course_grade_records')
            ->whereNotNull('grade')
            ->select('student_enrollment_id')
            ->selectRaw('ROUND((SUM(grade * COALESCE(weight, 1)) * 1.0) / NULLIF(SUM(COALESCE(weight, 1)), 0), 2) as numeric_grade')
            ->groupBy('student_enrollment_id');

        $rows = DB::table('student_enrollments')
            ->join('courses', 'courses.id', '=', 'student_enrollments.course_id')
            ->leftJoinSub($gradeStats, 'grade_stats', function ($join): void {
                $join->on('grade_stats.student_enrollment_id', '=', 'student_enrollments.id');
            })
            ->where('student_enrollments.student_id', $studentId)
            ->where('student_enrollments.status', '!=', 'dropped')
            ->select('student_enrollments.status as enrollment_status', 'courses.ects', 'grade_stats.numeric_grade')
            ->get();

        $gradedRows = $rows->filter(fn (object $row): bool => $row->numeric_grade !== null);

        $averageGrade = $gradedRows->isEmpty() ? 0 : round((float) $gradedRows->avg('numeric_grade'), 2);

        $completedRows = $rows->filter(fn (object $row): bool => $row->enrollment_status === 'completed');
        $passedRows = $completedRows->filter(fn (object $row): bool => $row->numeric_grade !== null && (float) $row->numeric_grade >= 6);

        $totalCreditsEarned = (int) $passedRows->sum('ects');

        $requiredCredits = (int) (DB::table('students')
            ->leftJoin('programs', 'programs.id', '=', 'students.program_id')
            ->where('students.id', $studentId)
            ->value('programs.required_credits') ?? 0);

        $academicStanding = $gradedRows->isEmpty() ? '' : ((float) $gradedRows->avg('numeric_grade') >= 6 ? 'Good standing' : 'Academic risk');

        return [
            'averageGrade' => floor($averageGrade) === $averageGrade ? (string) (int) $averageGrade : number_format($averageGrade, 1),
            'creditsEarned' => (string) $totalCreditsEarned,
            'creditsRequired' => (string) $requiredCredits,
            'academicStanding' => $academicStanding,
        ];
    }

    private function initials(string $name): string
    {
        $parts = array_values(array_filter(explode(' ', trim($name))));

        if ($parts === []) {
            return '';
        }

        return strtoupper(substr($parts[0], 0, 1).substr($parts[count($parts) - 1], 0, 1));
    }
}
