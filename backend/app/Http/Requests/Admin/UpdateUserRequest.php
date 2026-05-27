<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('id') ?? $this->route('user') ?? $this->segment(4);

        // Find the student ID if applicable to ignore unique validation
        $studentId = null;
        if ($userId) {
            $studentId = \App\Models\Identity\Student::where('user_id', $userId)
                ->value('id');
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $userId],
            'password' => ['nullable', 'string', 'min:8'],
            'faculty_id' => ['nullable', 'integer', 'exists:faculties,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],

            // Student fields
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:20'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'personal_number' => ['nullable', 'string', 'max:50', 'unique:students,personal_number,' . $studentId],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:100'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],

            // Professor fields
            'title' => ['nullable', 'string', 'max:100'],
            'office' => ['nullable', 'string', 'max:50'],
            'office_hours' => ['nullable', 'string', 'max:255'],
            'consultation' => ['nullable', 'string', 'max:255'],
        ];
    }
}
