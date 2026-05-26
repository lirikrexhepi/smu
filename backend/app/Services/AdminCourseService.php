<?php

namespace App\Services;

use App\Models\Academic\Course;
use App\Models\Academic\CourseSchedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class AdminCourseService
{
    /**
     * List all courses with searching and filtering.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function listCourses(array $filters): array
    {
        $query = Course::with(['department', 'semester']);

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', $search)
                  ->orWhere('code', 'like', $search);
            });
        }

        if (!empty($filters['department_id']) && $filters['department_id'] !== 'all') {
            $query->where('department_id', (int) $filters['department_id']);
        }

        if (!empty($filters['semester_id']) && $filters['semester_id'] !== 'all') {
            $query->where('semester_id', (int) $filters['semester_id']);
        }

        $courses = $query->orderBy('id', 'desc')->paginate(15);

        $mappedItems = collect($courses->items())->map(function (Course $course): array {
            $studentCount = DB::table('student_enrollments')
                ->where('course_id', $course->id)
                ->where('status', 'active')
                ->count();

            $professors = DB::table('course_professor')
                ->join('professors', 'professors.id', '=', 'course_professor.professor_id')
                ->join('users', 'users.id', '=', 'professors.user_id')
                ->where('course_professor.course_id', $course->id)
                ->pluck('users.name')
                ->all();

            $schedule = DB::table('course_schedules')
                ->where('course_id', $course->id)
                ->first();

            return [
                'id' => $course->id,
                'courseKey' => $course->course_key,
                'code' => $course->code,
                'name' => $course->name,
                'department' => $course->department?->name,
                'departmentId' => $course->department_id,
                'semester' => $course->semester?->name,
                'semesterId' => $course->semester_id,
                'ects' => $course->ects,
                'status' => $course->status ?? 'Active',
                'room' => $course->room,
                'studentsCount' => $studentCount,
                'professors' => $professors,
                'scheduleDays' => $schedule?->days_label,
                'scheduleTime' => $schedule?->time_label,
            ];
        })->all();

        return [
            'items' => $mappedItems,
            'meta' => [
                'current_page' => $courses->currentPage(),
                'last_page' => $courses->lastPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
            ]
        ];
    }

    /**
     * Get a specific course's detail.
     */
    public function getCourse(int $id): array
    {
        $course = Course::with(['department', 'semester'])->findOrFail($id);

        $assignedProfessors = DB::table('course_professor')
            ->where('course_id', $course->id)
            ->pluck('professor_id')
            ->all();

        $schedule = DB::table('course_schedules')
            ->where('course_id', $course->id)
            ->first();

        $days = [];
        if ($schedule && $schedule->days) {
            $days = json_decode($schedule->days, true) ?? [];
        }

        return [
            'id' => $course->id,
            'courseKey' => $course->course_key,
            'code' => $course->code,
            'name' => $course->name,
            'departmentId' => $course->department_id,
            'semesterId' => $course->semester_id,
            'ects' => $course->ects,
            'status' => $course->status ?? 'Active',
            'room' => $course->room,
            'description' => $course->description,
            'learningOutcomes' => is_string($course->learning_outcomes) ? json_decode($course->learning_outcomes, true) : $course->learning_outcomes,
            'topics' => is_string($course->topics) ? json_decode($course->topics, true) : $course->topics,
            'gradingBreakdown' => $course->grading_breakdown,
            'professorIds' => $assignedProfessors,
            'schedule' => $schedule ? [
                'days' => $days,
                'starts_at' => substr($schedule->starts_at ?? '', 0, 5),
                'ends_at' => substr($schedule->ends_at ?? '', 0, 5),
            ] : null,
        ];
    }

    /**
     * Create a course transactionally.
     */
    public function createCourse(array $data): Course
    {
        return DB::transaction(function () use ($data): Course {
            $courseKey = 'crs-' . Str::slug($data['name']) . '-' . rand(100, 999);

            $course = Course::create([
                'course_key' => $courseKey,
                'code' => $data['code'],
                'name' => $data['name'],
                'department_id' => $data['department_id'],
                'semester_id' => $data['semester_id'],
                'ects' => $data['ects'],
                'status' => 'Active',
                'room' => $data['room'] ?? null,
                'description' => $data['description'] ?? null,
                'learning_outcomes' => $data['learning_outcomes'] ?? null,
                'topics' => $data['topics'] ?? null,
                'grading_breakdown' => $data['grading_breakdown'] ?? null,
            ]);

            // Assign professors
            if (!empty($data['professor_ids'])) {
                foreach ($data['professor_ids'] as $profId) {
                    DB::table('course_professor')->insert([
                        'course_id' => $course->id,
                        'professor_id' => $profId,
                        'role' => 'instructor',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Create schedule if present
            if (!empty($data['schedule']['days'])) {
                $days = $data['schedule']['days'];
                $starts = $data['schedule']['starts_at'] ?? '09:00';
                $ends = $data['schedule']['ends_at'] ?? '10:30';
                
                $daysShort = array_map(fn($day) => substr($day, 0, 3), $days);
                $daysLabel = implode(', ', $daysShort);
                $timeLabel = "$starts - $ends";

                DB::table('course_schedules')->insert([
                    'course_id' => $course->id,
                    'days_label' => $daysLabel,
                    'days' => json_encode($days),
                    'time_label' => $timeLabel,
                    'starts_at' => $starts,
                    'ends_at' => $ends,
                    'room' => $data['room'] ?? null,
                    'label' => 'Lecture',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $course;
        });
    }

    /**
     * Update a course.
     */
    public function updateCourse(int $id, array $data): Course
    {
        return DB::transaction(function () use ($id, $data): Course {
            $course = Course::findOrFail($id);

            $course->update([
                'code' => $data['code'],
                'name' => $data['name'],
                'department_id' => $data['department_id'],
                'semester_id' => $data['semester_id'],
                'ects' => $data['ects'],
                'status' => $data['status'] ?? $course->status ?? 'Active',
                'room' => $data['room'] ?? null,
                'description' => $data['description'] ?? null,
                'learning_outcomes' => $data['learning_outcomes'] ?? null,
                'topics' => $data['topics'] ?? null,
                'grading_breakdown' => $data['grading_breakdown'] ?? null,
            ]);

            // Sync professors
            DB::table('course_professor')->where('course_id', $course->id)->delete();
            if (!empty($data['professor_ids'])) {
                foreach ($data['professor_ids'] as $profId) {
                    DB::table('course_professor')->insert([
                        'course_id' => $course->id,
                        'professor_id' => $profId,
                        'role' => 'instructor',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Sync schedule
            DB::table('course_schedules')->where('course_id', $course->id)->delete();
            if (!empty($data['schedule']['days'])) {
                $days = $data['schedule']['days'];
                $starts = $data['schedule']['starts_at'] ?? '09:00';
                $ends = $data['schedule']['ends_at'] ?? '10:30';

                $daysShort = array_map(fn($day) => substr($day, 0, 3), $days);
                $daysLabel = implode(', ', $daysShort);
                $timeLabel = "$starts - $ends";

                DB::table('course_schedules')->insert([
                    'course_id' => $course->id,
                    'days_label' => $daysLabel,
                    'days' => json_encode($days),
                    'time_label' => $timeLabel,
                    'starts_at' => $starts,
                    'ends_at' => $ends,
                    'room' => $data['room'] ?? null,
                    'label' => 'Lecture',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $course;
        });
    }

    /**
     * Delete a course.
     */
    public function deleteCourse(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $course = Course::findOrFail($id);

            // Delete dependencies explicitly to prevent FK blocks
            DB::table('course_professor')->where('course_id', $id)->delete();
            DB::table('course_schedules')->where('course_id', $id)->delete();
            DB::table('student_enrollments')->where('course_id', $id)->delete();

            $course->delete();
        });
    }
}
