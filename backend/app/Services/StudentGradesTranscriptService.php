<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class StudentGradesTranscriptService
{
    /**
     * @return array<string, mixed>
     */
    public function forRequest(Request $request): array
    {
        $user = $request->user();
        $student = DB::table('students')->where('user_id', $user?->id)->first();

        if ($student === null) {
            return $this->emptyResponse($user?->public_id);
        }

        $semester = $this->selectedSemester((int) $student->id, (string) $request->query('semester', ''));
        $courseId = trim((string) $request->query('courseId', ''));
        $rows = $this->courseGradeRows((int) $student->id, $semester, $courseId);

        return [
            'studentKey' => (string) ($user?->public_id ?? $student->student_key ?? ''),
            'academicYear' => (string) ($student->academic_year_label ?? $this->summary((int) $student->id)?->academic_year ?? ''),
            'selectedSemester' => $semester,
            'selectedCourseId' => $courseId !== '' && $courseId !== 'all' ? $courseId : null,
            'filters' => [
                'semesters' => $this->semesterOptions((int) $student->id),
                'courses' => $this->courseOptions((int) $student->id, $semester),
            ],
            'summary' => $this->summaryPayload((int) $student->id, $rows),
            'gradeOverview' => $rows->map(fn (object $row): array => [
                'courseId' => (string) $row->course_key,
                'courseCode' => (string) $row->course_code,
                'numericGrade' => (float) $row->numeric_grade,
            ])->values()->all(),
            'gradeDistribution' => $this->gradeDistribution($rows),
            'courseGrades' => $rows->map(fn (object $row): array => [
                'courseId' => (string) $row->course_key,
                'courseCode' => (string) $row->course_code,
                'courseName' => (string) $row->course_name,
                'credits' => (int) $row->ects,
                'numericGrade' => (float) $row->numeric_grade,
                'displayGrade' => $this->gradeLabel((int) round((float) $row->numeric_grade)),
                'gradePoints' => (float) $row->grade_points,
                'status' => (string) $row->status,
                'statusLabel' => (string) ($row->status_label ?: ucfirst(str_replace('-', ' ', (string) $row->status))),
            ])->values()->all(),
            'transcriptAction' => $this->transcriptAction((int) $student->id),
        ];
    }

    private function selectedSemester(int $studentId, string $requested): string
    {
        $requested = trim($requested);
        $baseQuery = DB::table('transcript_semester_options')
            ->where('student_id', $studentId);

        if ($requested !== '' && $requested !== 'all') {
            $option = (clone $baseQuery)
                ->where(function ($query) use ($requested): void {
                    $query->where('semester_code', $requested)
                        ->orWhere('label', $requested);
                })
                ->first();

            if ($option !== null) {
                return (string) $option->semester_code;
            }
        }

        $default = (clone $baseQuery)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->first();

        if ($default !== null) {
            return (string) $default->semester_code;
        }

        $fallback = DB::table('transcript_course_grades')
            ->where('student_id', $studentId)
            ->orderByDesc('semester_code')
            ->value('semester_code');

        return (string) ($fallback ?? '');
    }

    /**
     * @return array<int, array{id: string, label: string}>
     */
    private function semesterOptions(int $studentId): array
    {
        $options = DB::table('transcript_semester_options')
            ->where('student_id', $studentId)
            ->orderByDesc('is_default')
            ->orderByDesc('semester_code')
            ->get()
            ->map(fn (object $option): array => [
                'id' => (string) $option->semester_code,
                'label' => (string) $option->label,
            ])
            ->values()
            ->all();

        if ($options !== []) {
            return $options;
        }

        return DB::table('transcript_course_grades')
            ->where('student_id', $studentId)
            ->select('semester_code')
            ->distinct()
            ->orderByDesc('semester_code')
            ->get()
            ->map(fn (object $option): array => [
                'id' => (string) $option->semester_code,
                'label' => (string) $option->semester_code,
            ])
            ->all();
    }

    /**
     * @return array<int, array{courseId: string, code: string, name: string, label: string}>
     */
    private function courseOptions(int $studentId, string $semester): array
    {
        return DB::table('transcript_course_grades')
            ->join('courses', 'courses.id', '=', 'transcript_course_grades.course_id')
            ->where('transcript_course_grades.student_id', $studentId)
            ->when($semester !== '', fn ($query) => $query->where('transcript_course_grades.semester_code', $semester))
            ->orderBy('courses.code')
            ->select('courses.course_key', 'courses.code', 'courses.name')
            ->get()
            ->map(fn (object $course): array => [
                'courseId' => (string) $course->course_key,
                'code' => (string) $course->code,
                'name' => (string) $course->name,
                'label' => trim((string) $course->code.' - '.(string) $course->name),
            ])
            ->all();
    }

    /**
     * @return Collection<int, object>
     */
    private function courseGradeRows(int $studentId, string $semester, string $courseId): Collection
    {
        return DB::table('transcript_course_grades')
            ->join('courses', 'courses.id', '=', 'transcript_course_grades.course_id')
            ->leftJoin('semesters', 'semesters.code', '=', 'transcript_course_grades.semester_code')
            ->where('transcript_course_grades.student_id', $studentId)
            ->when($semester !== '', fn ($query) => $query->where('transcript_course_grades.semester_code', $semester))
            ->when($courseId !== '' && $courseId !== 'all', function ($query) use ($courseId): void {
                $query->where(function ($query) use ($courseId): void {
                    $query->where('courses.course_key', $courseId)
                        ->orWhere('courses.code', $courseId);

                    if (ctype_digit($courseId)) {
                        $query->orWhere('courses.id', (int) $courseId);
                    }
                });
            })
            ->orderByDesc('semesters.number')
            ->orderBy('courses.code')
            ->select(
                'transcript_course_grades.*',
                'courses.course_key',
                'courses.code as course_code',
                'courses.name as course_name',
                'courses.ects',
            )
            ->get();
    }

    private function summary(int $studentId): ?object
    {
        return DB::table('transcript_summaries')
            ->where('student_id', $studentId)
            ->first();
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array<string, mixed>
     */
    private function summaryPayload(int $studentId, Collection $rows): array
    {
        $summary = $this->summary($studentId);

        if ($summary === null) {
            $passedRows = $rows->where('status', 'passed');

            return [
                'averageGrade' => $rows->isEmpty() ? 0 : round((float) $rows->avg('numeric_grade'), 2),
                'gradeStatus' => $rows->isEmpty() ? 'No grades published yet' : 'Grades available',
                'totalCreditsEarned' => (int) $passedRows->sum('ects'),
                'requiredCredits' => 0,
                'coursesCompleted' => $passedRows->count(),
                'completionPercentage' => 0,
                'academicStanding' => '',
                'eligibilityStatus' => '',
            ];
        }

        return [
            'averageGrade' => (float) $summary->average_grade,
            'gradeStatus' => (string) ($summary->grade_status ?? ''),
            'totalCreditsEarned' => (int) $summary->total_credits_earned,
            'requiredCredits' => (int) $summary->required_credits,
            'coursesCompleted' => (int) $summary->courses_completed,
            'completionPercentage' => (int) $summary->completion_percentage,
            'academicStanding' => (string) ($summary->academic_standing ?? ''),
            'eligibilityStatus' => (string) ($summary->eligibility_status ?? ''),
        ];
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array<int, array{grade: int, label: string, count: int, percentage: int}>
     */
    private function gradeDistribution(Collection $rows): array
    {
        $counts = $rows
            ->map(fn (object $row): int => (int) round((float) $row->numeric_grade))
            ->filter(fn (int $grade): bool => $grade >= 5 && $grade <= 10)
            ->countBy();

        $total = max(1, (int) $counts->sum());

        return collect([10, 9, 8, 7, 6, 5])
            ->map(fn (int $grade): array => [
                'grade' => $grade,
                'label' => $this->gradeLabel($grade),
                'count' => (int) ($counts[$grade] ?? 0),
                'percentage' => (int) round(((int) ($counts[$grade] ?? 0) / $total) * 100),
            ])
            ->all();
    }

    /**
     * @return array{label: string, status: string}
     */
    private function transcriptAction(int $studentId): array
    {
        $summary = $this->summary($studentId);

        if ($summary === null) {
            return [
                'label' => 'Transcript unavailable',
                'status' => 'disabled',
            ];
        }

        return [
            'label' => (string) ($summary->transcript_action_label ?? 'Download unofficial transcript'),
            'status' => (string) ($summary->transcript_action_status ?? 'available'),
        ];
    }

    private function gradeLabel(int $grade): string
    {
        return match ($grade) {
            10 => '10 Excellent',
            9 => '9 Very Good',
            8 => '8 Good',
            7 => '7 Satisfactory',
            6 => '6 Sufficient',
            default => '5 Failed',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyResponse(?string $studentKey): array
    {
        return [
            'studentKey' => (string) ($studentKey ?? ''),
            'academicYear' => '',
            'selectedSemester' => '',
            'selectedCourseId' => null,
            'filters' => ['semesters' => [], 'courses' => []],
            'summary' => [
                'averageGrade' => 0,
                'gradeStatus' => 'No grades published yet',
                'totalCreditsEarned' => 0,
                'requiredCredits' => 0,
                'coursesCompleted' => 0,
                'completionPercentage' => 0,
                'academicStanding' => '',
                'eligibilityStatus' => '',
            ],
            'gradeOverview' => [],
            'gradeDistribution' => [],
            'courseGrades' => [],
            'transcriptAction' => [
                'label' => 'Transcript unavailable',
                'status' => 'disabled',
            ],
        ];
    }
}
