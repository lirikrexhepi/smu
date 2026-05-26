<?php

namespace App\Services\Gradebook;

use App\Models\Identity\User;
use App\Models\Academic\Course;
use App\Models\Identity\Student;
use App\Models\Gradebook\StudentEnrollment;
use App\Models\Academic\CourseEvent;
use App\Models\Gradebook\CourseGradeRecord;
use Illuminate\Support\Facades\DB;

final class ProfessorGradebookService
{
    /**
     * @return array<string, mixed>
     */
    public function getGradebookData(User $user): array
    {
        $professor = $user->professor;
        if ($professor === null) {
            return [];
        }

        $courses    = $professor->courses;
        $courseIds  = $courses->pluck('id')->all();

        // Retrieve student enrollments with dynamic relationships via Eloquent
        $enrollments = StudentEnrollment::with(['student.user', 'course', 'gradeRecords'])
            ->whereIn('course_id', $courseIds)
            ->get();

        $rows = $enrollments->map(function (StudentEnrollment $enrollment): array {
            $midterm = $enrollment->gradeRecords->where('grade_key', 'midterm')->first();
            $project = $enrollment->gradeRecords->where('grade_key', 'project')->first()
                ?? $enrollment->gradeRecords->where('grade_key', 'project-submission')->first();
            $final   = $enrollment->gradeRecords->where('grade_key', 'final')->first();

            $midtermVal = $midterm !== null ? (float) ($midterm->grade ?? $this->parseScore($midterm->score)) : 0;
            $projectVal = $project !== null ? (float) ($project->grade ?? $this->parseScore($project->score)) : 0;
            $finalVal   = $final   !== null ? (float) ($final->grade   ?? $this->parseScore($final->score))   : null;

            if ($finalVal !== null) {
                $average = ($midtermVal * 0.3) + ($projectVal * 0.3) + ($finalVal * 0.4);
                $status  = $average >= 6.0 ? 'Passed' : 'At Risk';
            } else {
                $average = ($midtermVal * 0.5) + ($projectVal * 0.5);
                $status  = $average < 6.0 ? 'At Risk' : 'In Progress';
            }

            return [
                'id'         => 'g-' . $enrollment->id,
                'student'    => (string) $enrollment->student?->user?->name,
                'studentId'  => (string) $enrollment->student?->student_key,
                'courseCode' => (string) $enrollment->course?->code,
                'midterm'    => round($midtermVal, 1),
                'project'    => round($projectVal, 1),
                'final'      => $finalVal !== null ? round($finalVal, 1) : null,
                'average'    => round($average, 1),
                'status'     => $status,
            ];
        })->values()->all();

        // Assessments — all real counts, no estimates
        $assessments = CourseEvent::with('course')
            ->whereIn('course_id', $courseIds)
            ->where('category', 'deadline')
            ->orderBy('event_date')
            ->get()
            ->map(function (CourseEvent $event): array {
                $total = DB::table('student_enrollments')
                    ->where('course_id', $event->course_id)
                    ->whereIn('status', ['active', 'registered', 'upcoming', 'completed'])
                    ->count();

                $graded = DB::table('course_grade_records')
                    ->join('student_enrollments', 'course_grade_records.student_enrollment_id', '=', 'student_enrollments.id')
                    ->where('student_enrollments.course_id', $event->course_id)
                    ->where('course_grade_records.grade_key', $event->event_key)
                    ->whereNotNull('course_grade_records.grade')
                    ->count();

                // Submitted = number who have ANY grade record for this event key (graded or pending)
                $submitted = DB::table('course_grade_records')
                    ->join('student_enrollments', 'course_grade_records.student_enrollment_id', '=', 'student_enrollments.id')
                    ->where('student_enrollments.course_id', $event->course_id)
                    ->where('course_grade_records.grade_key', $event->event_key)
                    ->count();

                return [
                    'id'          => 'a-' . $event->id,
                    'courseCode'  => (string) $event->course?->code,
                    'title'       => (string) $event->title,
                    'type'        => (string) $event->type,
                    'dueDate'     => date('d M Y', strtotime((string) $event->event_date)),
                    'submitted'   => $submitted,
                    'total'       => $total,
                    'graded'      => $graded,
                ];
            })
            ->all();

        return [
            'gradebook'   => $rows,
            'assessments' => $assessments,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function saveGrade(User $user, array $data): void
    {
        $professor = $user->professor;
        if ($professor === null) {
            throw new \Exception('Professor not found');
        }

        $studentKey = $data['studentId'] ?? '';
        $courseCode = $data['courseCode'] ?? '';
        $component  = $data['component'] ?? 'midterm';
        $gradeVal   = (float) ($data['grade'] ?? 0);

        $student = Student::where('student_key', $studentKey)->first();
        $course  = Course::where('code', $courseCode)->first();

        if ($student === null || $course === null) {
            throw new \Exception('Student or Course not found');
        }

        $enrollment = StudentEnrollment::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->first();

        if ($enrollment === null) {
            throw new \Exception('Student enrollment not found');
        }

        $title = match ($component) {
            'midterm' => 'Midterm Exam',
            'project' => 'Project Submission',
            'final'   => 'Final Exam',
            default   => 'Course Work',
        };

        $weightLabel = match ($component) {
            'midterm' => '30%',
            'project' => '30%',
            'final'   => '40%',
            default   => '10%',
        };

        $now = now();
        \App\Models\Gradebook\CourseGradeRecord::updateOrCreate([
            'student_enrollment_id' => $enrollment->id,
            'grade_key'             => $component,
        ], [
            'title'        => $title,
            'type'         => ucfirst($component),
            'score'        => $gradeVal . '/10',
            'weight_label' => $weightLabel,
            'grade'        => $gradeVal,
            'weight'       => match ($component) {
                'midterm' => 30,
                'project' => 30,
                'final'   => 40,
                default   => 10,
            },
            'graded_on'  => $now->toDateString(),
            'date_label' => $now->format('M j, Y'),
            'status'     => $component === 'final' ? 'Final' : 'Graded',
        ]);
    }

    private function parseScore(?string $score): float
    {
        if ($score === null || $score === '') {
            return 0;
        }
        $parts = explode('/', $score);
        return count($parts) > 0 ? (float) $parts[0] : 0;
    }
}
