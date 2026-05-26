<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class CreateUserRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', 'in:student,professor,admin'],
            'faculty_id' => ['nullable', 'integer', 'exists:faculties,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],

            // Student fields
            'program_id' => ['nullable', 'required_if:role,student', 'integer', 'exists:programs,id'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:20'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'personal_number' => ['nullable', 'required_if:role,student', 'string', 'max:50', 'unique:students,personal_number'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:100'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],

            // Professor fields
            'title' => ['nullable', 'required_if:role,professor', 'string', 'max:100'],
            'office' => ['nullable', 'string', 'max:50'],
            'office_hours' => ['nullable', 'string', 'max:255'],
            'consultation' => ['nullable', 'string', 'max:255'],
        ];
    }
}
