<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class StudentGradesTranscriptService
{
    public function __construct(private readonly StudentAcademicRecordsService $records) {}

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
            'academicYear' => (string) $this->academicYear((int) $student->id),
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
            'courseGrades' => $rows->map(fn (object $row): array => $this->courseGradePayload($row))->values()->all(),
            'transcriptAction' => [
                'label' => 'Download unofficial transcript',
                'status' => $rows->isEmpty() ? 'disabled' : 'available',
            ],
        ];
    }

    private function selectedSemester(int $studentId, string $requested): string
    {
        $requested = trim($requested);

        if ($requested !== '') {
            if ($requested === 'all') {
                return 'all';
            }

            $exists = DB::table('student_enrollments')
                ->join('semesters', 'semesters.id', '=', 'student_enrollments.semester_id')
                ->where('student_enrollments.student_id', $studentId)
                ->where(function ($query) use ($requested): void {
                    $query->where('semesters.code', $requested)
                        ->orWhere('semesters.name', $requested);
                })
                ->exists();

            if ($exists) {
                return $requested;
            }
        }

        return 'all';
    }

    /**
     * @return array<int, array{id: string, label: string}>
     */
    private function semesterOptions(int $studentId): array
    {
        $options = DB::table('student_enrollments')
            ->join('semesters', 'semesters.id', '=', 'student_enrollments.semester_id')
            ->where('student_enrollments.student_id', $studentId)
            ->where('student_enrollments.status', '!=', 'dropped')
            ->select('semesters.code', 'semesters.name', 'semesters.is_current', 'semesters.number', 'semesters.id')
            ->distinct()
            ->orderByDesc('semesters.is_current')
            ->orderByDesc('semesters.number')
            ->orderByDesc('semesters.id')
            ->get()
            ->map(fn (object $semester): array => [
                'id' => (string) $semester->code,
                'label' => (string) $semester->name,
            ])
            ->values()
            ->all();

        return array_merge([
            ['id' => 'all', 'label' => 'All Semesters'],
        ], $options);
    }

    /**
     * @return array<int, array{courseId: string, code: string, name: string, label: string}>
     */
    private function courseOptions(int $studentId, string $semester): array
    {
        return DB::table('student_enrollments')
            ->join('courses', 'courses.id', '=', 'student_enrollments.course_id')
            ->leftJoin('semesters', 'semesters.id', '=', 'student_enrollments.semester_id')
            ->where('student_enrollments.student_id', $studentId)
            ->where('student_enrollments.status', '!=', 'dropped')
            ->when($semester !== '' && $semester !== 'all', function ($query) use ($semester): void {
                $query->where(function ($query) use ($semester): void {
                    $query->where('semesters.code', $semester)
                        ->orWhere('semesters.name', $semester);
                });
            })
            ->orderByDesc('semesters.number')
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
        $query = $this->courseGradeBaseQuery($studentId)
            ->when($semester !== '' && $semester !== 'all', function ($query) use ($semester): void {
                $query->where(function ($query) use ($semester): void {
                    $query->where('semesters.code', $semester)
                        ->orWhere('semesters.name', $semester);
                });
            })
            ->when($courseId !== '' && $courseId !== 'all', function ($query) use ($courseId): void {
                $query->where(function ($query) use ($courseId): void {
                    $query->where('courses.course_key', $courseId)
                        ->orWhere('courses.code', $courseId);

                    if (ctype_digit($courseId)) {
                        $query->orWhere('courses.id', (int) $courseId);
                    }
                });
            });

        return $query
            ->orderByDesc('semesters.number')
            ->orderBy('courses.code')
            ->get();
    }

    private function courseGradeBaseQuery(int $studentId): Builder
    {
        return DB::table('student_enrollments')
            ->join('courses', 'courses.id', '=', 'student_enrollments.course_id')
            ->leftJoin('semesters', 'semesters.id', '=', 'student_enrollments.semester_id')
            ->leftJoinSub($this->records->gradeAveragesSubquery(), 'grade_stats', function ($join): void {
                $join->on('grade_stats.student_enrollment_id', '=', 'student_enrollments.id');
            })
            ->where('student_enrollments.student_id', $studentId)
            ->where('student_enrollments.status', '!=', 'dropped')
            ->whereNotNull('grade_stats.numeric_grade')
            ->select(
                'student_enrollments.id as enrollment_id',
                'student_enrollments.status as enrollment_status',
                'courses.course_key',
                'courses.code as course_code',
                'courses.name as course_name',
                'courses.ects',
                'semesters.code as semester_code',
                'semesters.name as semester_name',
                'semesters.number as semester_number',
                'grade_stats.numeric_grade',
                'grade_stats.grade_count',
            );
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array<string, mixed>
     */
    private function summaryPayload(int $studentId, Collection $rows): array
    {
        $completedRows = $this->courseGradeBaseQuery($studentId)
            ->where('student_enrollments.status', 'completed')
            ->get();
        $passedRows = $completedRows->filter(fn (object $row): bool => (float) $row->numeric_grade >= 6);
        $requiredCredits = (int) (DB::table('students')
            ->leftJoin('programs', 'programs.id', '=', 'students.program_id')
            ->where('students.id', $studentId)
            ->value('programs.required_credits') ?? 0);

        return [
            'averageGrade' => $rows->isEmpty() ? 0 : round((float) $rows->avg('numeric_grade'), 2),
            'gradeStatus' => $rows->isEmpty() ? 'No grades published yet' : ($rows->filter(fn (object $row): bool => (float) $row->numeric_grade >= 6)->isEmpty() ? 'At risk' : 'On track'),
            'totalCreditsEarned' => (int) $passedRows->sum('ects'),
            'requiredCredits' => $requiredCredits,
            'coursesCompleted' => $passedRows->count(),
            'completionPercentage' => $requiredCredits > 0 ? (int) round(((int) $passedRows->sum('ects') / $requiredCredits) * 100) : 0,
            'academicStanding' => $this->academicStanding($rows),
            'eligibilityStatus' => $passedRows->isEmpty() ? 'In progress' : 'Eligible to continue',
        ];
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array<int, array{grade: int, label: string, count: int, percentage: int}>
     */
    private function gradeDistribution(Collection $rows): array
    {
        $counts = $rows
            ->map(fn (object $row): int => max(5, min(10, (int) round((float) $row->numeric_grade))))
            ->countBy();

        $total = max(1, (int) $counts->sum());

        return collect([10, 9, 8, 7, 6, 5])
            ->map(fn (int $grade): array => [
                'grade' => $grade,
                'label' => $grade.' '.$this->records->gradeDescription($grade),
                'count' => (int) ($counts[$grade] ?? 0),
                'percentage' => (int) round(((int) ($counts[$grade] ?? 0) / $total) * 100),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function courseGradePayload(object $row): array
    {
        $status = $this->records->courseStatus((string) $row->enrollment_status, $row->numeric_grade);

        return [
            'courseId' => (string) $row->course_key,
            'courseCode' => (string) $row->course_code,
            'courseName' => (string) $row->course_name,
            'credits' => (int) $row->ects,
            'numericGrade' => (float) $row->numeric_grade,
            'displayGrade' => $this->records->gradeLabel($row->numeric_grade),
            'gradePoints' => (float) $row->numeric_grade,
            'status' => $status,
            'statusLabel' => $this->records->courseStatusLabel($status),
        ];
    }

    private function academicYear(int $studentId): string
    {
        return (string) (DB::table('student_enrollments')
            ->join('semesters', 'semesters.id', '=', 'student_enrollments.semester_id')
            ->leftJoin('academic_years', 'academic_years.id', '=', 'semesters.academic_year_id')
            ->where('student_enrollments.student_id', $studentId)
            ->orderByDesc('semesters.is_current')
            ->orderByDesc('semesters.number')
            ->value('academic_years.name') ?? '');
    }

    /**
     * @param  Collection<int, object>  $rows
     */
    private function academicStanding(Collection $rows): string
    {
        if ($rows->isEmpty()) {
            return '';
        }

        return (float) $rows->avg('numeric_grade') >= 6 ? 'Good standing' : 'Academic risk';
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyResponse(?string $studentKey): array
    {
        return [
            'studentKey' => (string) ($studentKey ?? ''),
            'academicYear' => '',
            'selectedSemester' => 'all',
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
