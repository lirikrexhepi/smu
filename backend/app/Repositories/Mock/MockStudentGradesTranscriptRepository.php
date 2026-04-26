<?php

declare(strict_types=1);

namespace App\Repositories\Mock;

use App\Data\MockData\SemsMockData;
use App\DTO\StudentGradesTranscriptData;
use App\Repositories\Contracts\StudentGradesTranscriptRepositoryInterface;
use RuntimeException;

final class MockStudentGradesTranscriptRepository implements StudentGradesTranscriptRepositoryInterface
{
    public function getForStudent(
        string $studentKey,
        ?string $semester = null,
        ?string $courseId = null,
    ): StudentGradesTranscriptData {
        $gradesTranscript = SemsMockData::studentGradesTranscript($studentKey);
        $studentCourses = SemsMockData::studentCourses($studentKey);
        $courseGrades = $this->courseGrades($gradesTranscript);
        $courseIds = $this->courseIds($studentCourses, $courseGrades);
        $courseLookup = $this->courseLookup($courseIds);
        $selectedSemester = $this->selectedSemester($gradesTranscript, $semester);
        $selectedCourseId = $this->selectedCourseId($courseId, $courseGrades);
        $filteredGrades = $this->filteredCourseGrades($courseGrades, $selectedSemester, $selectedCourseId);

        return new StudentGradesTranscriptData([
            'studentKey' => (string) ($gradesTranscript['studentKey'] ?? $studentKey),
            'academicYear' => (string) ($gradesTranscript['academicYear'] ?? ($studentCourses['academicYear'] ?? '')),
            'selectedSemester' => $selectedSemester,
            'selectedCourseId' => $selectedCourseId,
            'filters' => [
                'semesters' => $this->semesterOptions($gradesTranscript),
                'courses' => $this->courseOptions($courseGrades, $courseLookup),
            ],
            'summary' => $this->summary($gradesTranscript),
            'gradeOverview' => $this->gradeOverview($filteredGrades, $courseLookup),
            'gradeDistribution' => $this->gradeDistribution($gradesTranscript),
            'courseGrades' => $this->tableRows($filteredGrades, $courseLookup),
            'transcriptAction' => [
                'label' => (string) (($gradesTranscript['transcriptAction']['label'] ?? null) ?: 'View Full Transcript'),
                'status' => (string) (($gradesTranscript['transcriptAction']['status'] ?? null) ?: 'planned'),
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $gradesTranscript
     * @return list<array<string, mixed>>
     */
    private function courseGrades(array $gradesTranscript): array
    {
        $courseGrades = $gradesTranscript['courseGrades'] ?? [];

        if (!is_array($courseGrades) || !array_is_list($courseGrades)) {
            throw new RuntimeException('Student grades transcript mock data must contain a courseGrades list.');
        }

        return $courseGrades;
    }

    /**
     * @param array<string, mixed> $studentCourses
     * @param list<array<string, mixed>> $courseGrades
     * @return list<string>
     */
    private function courseIds(array $studentCourses, array $courseGrades): array
    {
        $courseIds = [];
        $enrollments = $studentCourses['courses'] ?? [];

        if (is_array($enrollments)) {
            foreach ($enrollments as $enrollment) {
                if (!is_array($enrollment)) {
                    continue;
                }

                $courseIds[] = $this->normalizedId((string) ($enrollment['courseId'] ?? ''));
            }
        }

        foreach ($courseGrades as $courseGrade) {
            $courseIds[] = $this->normalizedId((string) ($courseGrade['courseId'] ?? ''));
        }

        return array_values(array_unique(array_filter($courseIds)));
    }

    /**
     * @param list<string> $courseIds
     * @return array<string, array<string, mixed>>
     */
    private function courseLookup(array $courseIds): array
    {
        $courses = [];

        foreach ($courseIds as $courseId) {
            $course = SemsMockData::course($courseId);
            $courses[$this->normalizedId((string) ($course['courseId'] ?? $courseId))] = $course;
        }

        return $courses;
    }

    /**
     * @param array<string, mixed> $gradesTranscript
     */
    private function selectedSemester(array $gradesTranscript, ?string $semester): string
    {
        $semesters = $this->semesterOptions($gradesTranscript);

        if ($semester === null) {
            return (string) ($gradesTranscript['defaultSemester'] ?? ($semesters[0]['id'] ?? ''));
        }

        $requestedSemester = strtolower(trim($semester));

        foreach ($semesters as $option) {
            $id = strtolower((string) ($option['id'] ?? ''));
            $label = strtolower((string) ($option['label'] ?? ''));

            if ($requestedSemester === $id || $requestedSemester === $label) {
                return (string) ($option['id'] ?? '');
            }
        }

        throw new RuntimeException(sprintf('Student grades semester not found: %s', $semester));
    }

    /**
     * @param list<array<string, mixed>> $courseGrades
     */
    private function selectedCourseId(?string $courseId, array $courseGrades): ?string
    {
        if ($courseId === null) {
            return null;
        }

        $selectedCourseId = $this->normalizedId($courseId);

        if ($selectedCourseId === '') {
            return null;
        }

        foreach ($courseGrades as $courseGrade) {
            if ($this->normalizedId((string) ($courseGrade['courseId'] ?? '')) === $selectedCourseId) {
                return $selectedCourseId;
            }
        }

        throw new RuntimeException(sprintf('Student grade course not found: %s', $courseId));
    }

    /**
     * @param array<string, mixed> $gradesTranscript
     * @return list<array{id: string, label: string}>
     */
    private function semesterOptions(array $gradesTranscript): array
    {
        $semesters = $gradesTranscript['semesters'] ?? [];
        $options = [];

        if (!is_array($semesters)) {
            return $options;
        }

        foreach ($semesters as $semester) {
            if (!is_array($semester)) {
                continue;
            }

            $id = (string) ($semester['id'] ?? '');

            if ($id === '') {
                continue;
            }

            $options[] = [
                'id' => $id,
                'label' => (string) ($semester['label'] ?? $id),
            ];
        }

        return $options;
    }

    /**
     * @param list<array<string, mixed>> $courseGrades
     * @param array<string, array<string, mixed>> $courseLookup
     * @return list<array{courseId: string, code: string, name: string, label: string}>
     */
    private function courseOptions(array $courseGrades, array $courseLookup): array
    {
        $options = [];

        foreach ($courseGrades as $courseGrade) {
            $courseId = $this->normalizedId((string) ($courseGrade['courseId'] ?? ''));

            if ($courseId === '' || !isset($courseLookup[$courseId]) || isset($options[$courseId])) {
                continue;
            }

            $course = $courseLookup[$courseId];
            $code = (string) ($course['code'] ?? strtoupper($courseId));
            $name = (string) ($course['name'] ?? '');

            $options[$courseId] = [
                'courseId' => $courseId,
                'code' => $code,
                'name' => $name,
                'label' => trim($code . ' - ' . $name, ' -'),
            ];
        }

        return array_values($options);
    }

    /**
     * @param list<array<string, mixed>> $courseGrades
     * @return list<array<string, mixed>>
     */
    private function filteredCourseGrades(array $courseGrades, string $selectedSemester, ?string $selectedCourseId): array
    {
        $filteredGrades = [];

        foreach ($courseGrades as $courseGrade) {
            if ((string) ($courseGrade['semesterId'] ?? '') !== $selectedSemester) {
                continue;
            }

            $courseId = $this->normalizedId((string) ($courseGrade['courseId'] ?? ''));

            if ($selectedCourseId !== null && $courseId !== $selectedCourseId) {
                continue;
            }

            $filteredGrades[] = $courseGrade;
        }

        return $filteredGrades;
    }

    /**
     * @param array<string, mixed> $gradesTranscript
     * @return array<string, mixed>
     */
    private function summary(array $gradesTranscript): array
    {
        $summary = is_array($gradesTranscript['summary'] ?? null) ? $gradesTranscript['summary'] : [];

        return [
            'averageGrade' => (float) ($summary['averageGrade'] ?? 0),
            'gradeStatus' => (string) ($summary['gradeStatus'] ?? ''),
            'totalCreditsEarned' => (int) ($summary['totalCreditsEarned'] ?? 0),
            'requiredCredits' => (int) ($summary['requiredCredits'] ?? 0),
            'coursesCompleted' => (int) ($summary['coursesCompleted'] ?? 0),
            'completionPercentage' => (int) ($summary['completionPercentage'] ?? 0),
            'academicStanding' => (string) ($summary['academicStanding'] ?? ''),
            'eligibilityStatus' => (string) ($summary['eligibilityStatus'] ?? ''),
        ];
    }

    /**
     * @param list<array<string, mixed>> $courseGrades
     * @param array<string, array<string, mixed>> $courseLookup
     * @return list<array<string, mixed>>
     */
    private function gradeOverview(array $courseGrades, array $courseLookup): array
    {
        $overview = [];

        foreach ($courseGrades as $courseGrade) {
            $courseId = $this->normalizedId((string) ($courseGrade['courseId'] ?? ''));

            if ($courseId === '' || !isset($courseLookup[$courseId])) {
                continue;
            }

            $course = $courseLookup[$courseId];

            $overview[] = [
                'courseId' => $courseId,
                'courseCode' => (string) ($course['code'] ?? ''),
                'numericGrade' => (float) ($courseGrade['numericGrade'] ?? 0),
            ];
        }

        return $overview;
    }

    /**
     * @param array<string, mixed> $gradesTranscript
     * @return list<array<string, mixed>>
     */
    private function gradeDistribution(array $gradesTranscript): array
    {
        $distribution = $gradesTranscript['gradeDistribution'] ?? [];

        if (!is_array($distribution) || !array_is_list($distribution)) {
            return [];
        }

        $items = [];

        foreach ($distribution as $item) {
            if (!is_array($item)) {
                continue;
            }

            $items[] = [
                'grade' => (int) ($item['grade'] ?? 0),
                'label' => (string) ($item['label'] ?? ''),
                'count' => (int) ($item['count'] ?? 0),
                'percentage' => (int) ($item['percentage'] ?? 0),
            ];
        }

        return $items;
    }

    /**
     * @param list<array<string, mixed>> $courseGrades
     * @param array<string, array<string, mixed>> $courseLookup
     * @return list<array<string, mixed>>
     */
    private function tableRows(array $courseGrades, array $courseLookup): array
    {
        $rows = [];

        foreach ($courseGrades as $courseGrade) {
            $courseId = $this->normalizedId((string) ($courseGrade['courseId'] ?? ''));

            if ($courseId === '' || !isset($courseLookup[$courseId])) {
                continue;
            }

            $course = $courseLookup[$courseId];
            $numericGrade = (float) ($courseGrade['numericGrade'] ?? 0);
            $status = $this->statusFromGrade($numericGrade);

            $rows[] = [
                'courseId' => $courseId,
                'courseCode' => (string) ($course['code'] ?? ''),
                'courseName' => (string) ($course['name'] ?? ''),
                'credits' => (int) ($course['ects'] ?? 0),
                'numericGrade' => $numericGrade,
                'displayGrade' => $this->formatGrade($numericGrade),
                'gradePoints' => $numericGrade,
                'status' => $status,
                'statusLabel' => (string) ($courseGrade['statusLabel'] ?? $this->statusLabel($status)),
            ];
        }

        return $rows;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'in-progress' => 'In Progress',
            'failed' => 'Failed',
            default => 'Passed',
        };
    }

    private function statusFromGrade(float $grade): string
    {
        return $grade <= 5 ? 'failed' : 'passed';
    }

    private function formatGrade(float $value): string
    {
        if (floor($value) === $value) {
            return number_format($value, 0, '.', '');
        }

        return number_format($value, 1, '.', '');
    }

    private function normalizedId(string $id): string
    {
        return strtolower(trim($id));
    }
}
