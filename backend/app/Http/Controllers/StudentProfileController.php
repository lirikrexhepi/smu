<?php

namespace App\Http\Controllers;

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
        return ApiResponse::success(array_merge($this->profileData($request), [
            'fullName' => (string) $request->input('fullName', $request->user()?->name ?? ''),
            'email' => (string) $request->input('email', $request->user()?->email ?? ''),
            'phone' => (string) $request->input('phone', ''),
            'address' => (string) $request->input('address', ''),
            'dateOfBirth' => (string) $request->input('dateOfBirth', ''),
            'gender' => (string) $request->input('gender', ''),
            'nationality' => (string) $request->input('nationality', ''),
            'personalNumber' => (string) $request->input('personalNumber', ''),
            'emergencyContact' => [
                'name' => (string) $request->input('emergencyContactName', ''),
                'relationship' => (string) $request->input('emergencyContactRelationship', ''),
                'phone' => (string) $request->input('emergencyContactPhone', ''),
            ],
        ]), 'Student profile update placeholder.');
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        return ApiResponse::success($this->profileData($request), 'Student avatar upload placeholder.');
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

        return [
            'studentId' => (string) ($student?->student_number ?? $user?->institution_id ?? ''),
            'fullName' => $fullName,
            'initials' => $this->initials($fullName),
            'avatarUrl' => $user?->avatar_url,
            'status' => (string) ($student?->status ?? ''),
            'studentStatusLabel' => (string) ($student?->status_label ?? $student?->status ?? ''),
            'faculty' => (string) ($user?->faculty?->name ?? ''),
            'department' => (string) ($user?->department?->name ?? ''),
            'program' => '',
            'yearOfStudy' => (string) ($student?->year_of_study ?? ''),
            'semester' => (string) ($student?->current_semester_label ?? ''),
            'academicYear' => (string) ($student?->academic_year_label ?? ''),
            'currentGpa' => (string) ($student?->current_gpa ?? ''),
            'creditsEarned' => (string) ($student?->credits_earned ?? '0'),
            'creditsRequired' => (string) ($student?->credits_required ?? '0'),
            'academicStanding' => (string) ($student?->academic_standing ?? ''),
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

    private function initials(string $name): string
    {
        $parts = array_values(array_filter(explode(' ', trim($name))));

        if ($parts === []) {
            return '';
        }

        return strtoupper(substr($parts[0], 0, 1).substr($parts[count($parts) - 1], 0, 1));
    }
}
