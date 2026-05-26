<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Services\StudentAcademicRecordsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final readonly class StudentProfileController
{
    public function __construct(private StudentAcademicRecordsService $records) {}

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
        $student = $user === null ? null : DB::table('students')
            ->leftJoin('programs', 'programs.id', '=', 'students.program_id')
            ->where('students.user_id', $user->id)
            ->select('students.*', 'programs.name as program_name', 'programs.required_credits')
            ->first();
        $contact = $student === null ? null : DB::table('student_emergency_contacts')
            ->where('student_id', $student->id)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->first();
        $semester = $student === null ? null : $this->currentSemester((int) $student->id);
        $academic = $student === null ? $this->emptyAcademicSummary() : $this->academicSummary((int) $student->id, (int) ($student->required_credits ?? 0));

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
            'program' => (string) ($student?->program_name ?? ''),
            'yearOfStudy' => (string) ($student?->year_of_study ?? ''),
            'semester' => (string) ($semester?->name ?? ''),
            'academicYear' => (string) ($semester?->academic_year_name ?? ''),
            'currentGpa' => $academic['currentGpa'],
            'creditsEarned' => $academic['creditsEarned'],
            'creditsRequired' => (string) ($student?->required_credits ?? '0'),
            'academicStanding' => $academic['academicStanding'],
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

    private function currentSemester(int $studentId): ?object
    {
        return DB::table('student_enrollments')
            ->join('semesters', 'semesters.id', '=', 'student_enrollments.semester_id')
            ->leftJoin('academic_years', 'academic_years.id', '=', 'semesters.academic_year_id')
            ->where('student_enrollments.student_id', $studentId)
            ->where('student_enrollments.status', '!=', 'dropped')
            ->select('semesters.*', 'academic_years.name as academic_year_name')
            ->distinct()
            ->orderByDesc('semesters.is_current')
            ->orderByDesc('semesters.number')
            ->orderByDesc('semesters.id')
            ->first();
    }

    /**
     * @return array{currentGpa: string, creditsEarned: string, academicStanding: string}
     */
    private function academicSummary(int $studentId, int $requiredCredits): array
    {
        $rows = DB::table('student_enrollments')
            ->join('courses', 'courses.id', '=', 'student_enrollments.course_id')
            ->leftJoinSub($this->records->gradeAveragesSubquery(), 'grade_stats', function ($join): void {
                $join->on('grade_stats.student_enrollment_id', '=', 'student_enrollments.id');
            })
            ->where('student_enrollments.student_id', $studentId)
            ->where('student_enrollments.status', '!=', 'dropped')
            ->whereNotNull('grade_stats.numeric_grade')
            ->select('student_enrollments.status', 'courses.ects', 'grade_stats.numeric_grade')
            ->get();

        $completed = $rows->filter(fn (object $row): bool => $row->status === 'completed' && (float) $row->numeric_grade >= 6);
        $average = $rows->isEmpty() ? 0 : round((float) $rows->avg('numeric_grade'), 2);

        return [
            'currentGpa' => $this->records->decimalLabel($average),
            'creditsEarned' => (string) $completed->sum('ects'),
            'academicStanding' => $average >= 6 || $requiredCredits === 0 ? 'Good standing' : 'Academic risk',
        ];
    }

    /**
     * @return array{currentGpa: string, creditsEarned: string, academicStanding: string}
     */
    private function emptyAcademicSummary(): array
    {
        return ['currentGpa' => '', 'creditsEarned' => '0', 'academicStanding' => ''];
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
