<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateCourseRequest extends FormRequest
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
        $courseId = $this->route('id') ?? $this->route('course') ?? $this->segment(4);

        return [
            'code' => ['required', 'string', 'max:50', 'unique:courses,code,' . $courseId],
            'name' => ['required', 'string', 'max:255'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
            'ects' => ['required', 'integer', 'min:1', 'max:30'],
            'room' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'learning_outcomes' => ['nullable', 'array'],
            'topics' => ['nullable', 'array'],
            'grading_breakdown' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:Active,Exam Week,Closing'],
            'professor_ids' => ['nullable', 'array'],
            'professor_ids.*' => ['integer', 'exists:professors,id'],

            // Scheduling optional fields
            'schedule' => ['nullable', 'array'],
            'schedule.days' => ['nullable', 'array'],
            'schedule.days.*' => ['string', 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday'],
            'schedule.starts_at' => ['nullable', 'string'],
            'schedule.ends_at' => ['nullable', 'string'],
        ];
    }
}
