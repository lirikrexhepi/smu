<?php

declare(strict_types=1);

namespace App\Repositories\Mock;

use App\Data\MockData\SemsMockData;
use App\DTO\StudentAttendanceData;
use App\Repositories\Contracts\StudentAttendanceRepositoryInterface;
use RuntimeException;

final class MockStudentAttendanceRepository implements StudentAttendanceRepositoryInterface
{
    public function getForStudent(
        string $studentKey,
        ?string $courseId = null,
        ?string $semester = null,
        ?string $week = null,
    ): StudentAttendanceData {
        $attendance = SemsMockData::studentAttendance($studentKey);
        $studentCourses = SemsMockData::studentCourses($studentKey);
        $courseIds = $this->studentCourseIds($studentCourses);
        $courseLookup = $this->courseLookup($courseIds);
        $selectedCourseId = $this->selectedCourseId($courseId, $courseIds);

        $weeklySchedule = $this->weeklySchedule($attendance, $courseLookup, $selectedCourseId);
        $history = $this->history($attendance, $courseLookup, $selectedCourseId);

        $data = [
            'studentKey' => (string) ($attendance['studentKey'] ?? $studentKey),
            'semester' => (string) ($attendance['semester'] ?? ($studentCourses['semester'] ?? '')),
            'academicYear' => (string) ($attendance['academicYear'] ?? ($studentCourses['academicYear'] ?? '')),
            'selectedCourseId' => $selectedCourseId,
            'selectedSemester' => $semester ?? (string) ($attendance['semester'] ?? ($studentCourses['semester'] ?? '')),
            'selectedWeek' => $week ?? (string) (($attendance['week']['startDate'] ?? '') ?: ''),
            'filters' => [
                'courses' => $this->courseOptions($courseLookup),
                'semesters' => $this->semesterOptions($studentCourses, $courseLookup),
            ],
            'week' => $this->week($attendance, $week),
            'summary' => $this->summary($attendance, $selectedCourseId),
            'lastRecorded' => $this->lastRecorded($attendance, $courseLookup, $history, $selectedCourseId),
            'weeklySchedule' => $weeklySchedule,
            'history' => $history,
        ];

        return new StudentAttendanceData($data);
    }

    /**
     * @param array<string, mixed> $studentCourses
     * @return list<string>
     */
    private function studentCourseIds(array $studentCourses): array
    {
        $enrollments = $studentCourses['courses'] ?? [];

        if (!is_array($enrollments) || !array_is_list($enrollments)) {
            throw new RuntimeException('Student courses mock data must contain a courses list.');
        }

        $courseIds = [];

        foreach ($enrollments as $enrollment) {
            if (!is_array($enrollment)) {
                continue;
            }

            $courseId = $this->normalizedId((string) ($enrollment['courseId'] ?? ''));

            if ($courseId !== '') {
                $courseIds[] = $courseId;
            }
        }

        return array_values(array_unique($courseIds));
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
     * @param list<string> $courseIds
     */
    private function selectedCourseId(?string $courseId, array $courseIds): ?string
    {
        if ($courseId === null) {
            return null;
        }

        $selectedCourseId = $this->normalizedId($courseId);

        if ($selectedCourseId === '') {
            return null;
        }

        if (!in_array($selectedCourseId, $courseIds, true)) {
            throw new RuntimeException(sprintf('Student is not enrolled in course: %s', $courseId));
        }

        return $selectedCourseId;
    }

    /**
     * @param array<string, mixed> $attendance
     * @return array<string, mixed>
     */
    private function week(array $attendance, ?string $requestedWeek): array
    {
        $week = is_array($attendance['week'] ?? null) ? $attendance['week'] : [];

        return [
            'startDate' => (string) ($week['startDate'] ?? ''),
            'endDate' => (string) ($week['endDate'] ?? ''),
            'label' => (string) ($week['label'] ?? ''),
            'requestedDate' => $requestedWeek,
        ];
    }

    /**
     * @param array<string, mixed> $attendance
     * @return array<string, mixed>
     */
    private function summary(array $attendance, ?string $selectedCourseId): array
    {
        $summary = is_array($attendance['summary'] ?? null) ? $attendance['summary'] : [];

        if ($selectedCourseId !== null && is_array($attendance['courseSummaries'] ?? null)) {
            foreach ($attendance['courseSummaries'] as $courseSummary) {
                if (!is_array($courseSummary)) {
                    continue;
                }

                if ($this->normalizedId((string) ($courseSummary['courseId'] ?? '')) === $selectedCourseId) {
                    $summary = $courseSummary;
                    break;
                }
            }
        }

        $totalSessions = (int) ($summary['totalSessions'] ?? 0);
        $absences = (int) ($summary['absences'] ?? 0);
        $lateRecords = (int) ($summary['lateRecords'] ?? 0);
        $comparison = is_array($summary['comparisonVsLast4Weeks'] ?? null) ? $summary['comparisonVsLast4Weeks'] : [];
        $comparisonValue = (int) ($comparison['value'] ?? 0);
        $direction = (string) ($comparison['direction'] ?? ($comparisonValue < 0 ? 'down' : 'up'));

        return [
            'overallAttendance' => (int) ($summary['overallAttendance'] ?? 0),
            'presentSessions' => (int) ($summary['presentSessions'] ?? 0),
            'totalSessions' => $totalSessions,
            'absences' => $absences,
            'lateRecords' => $lateRecords,
            'absenceRate' => $this->percentage($absences, $totalSessions),
            'lateRate' => $this->percentage($lateRecords, $totalSessions),
            'comparisonVsLast4Weeks' => [
                'value' => abs($comparisonValue),
                'direction' => $direction,
                'label' => (string) ($comparison['label'] ?? 'vs last 4 weeks'),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $attendance
     * @param array<string, array<string, mixed>> $courseLookup
     * @return list<array<string, mixed>>
     */
    private function weeklySchedule(array $attendance, array $courseLookup, ?string $selectedCourseId): array
    {
        $days = $attendance['weeklySchedule'] ?? [];

        if (!is_array($days) || !array_is_list($days)) {
            return [];
        }

        $schedule = [];

        foreach ($days as $day) {
            if (!is_array($day)) {
                continue;
            }

            $blocks = [];
            $dayBlocks = $day['blocks'] ?? [];

            if (is_array($dayBlocks)) {
                foreach ($dayBlocks as $block) {
                    if (!is_array($block)) {
                        continue;
                    }

                    $courseId = $this->normalizedId((string) ($block['courseId'] ?? ''));

                    if ($courseId === '' || !isset($courseLookup[$courseId])) {
                        continue;
                    }

                    if ($selectedCourseId !== null && $courseId !== $selectedCourseId) {
                        continue;
                    }

                    $blocks[] = $this->block($block, $courseLookup[$courseId]);
                }
            }

            $schedule[] = [
                'date' => (string) ($day['date'] ?? ''),
                'dayName' => (string) ($day['dayName'] ?? ''),
                'dayShort' => (string) ($day['dayShort'] ?? ''),
                'dateLabel' => (string) ($day['dateLabel'] ?? ''),
                'isToday' => (bool) ($day['isToday'] ?? false),
                'blocks' => $blocks,
            ];
        }

        return $schedule;
    }

    /**
     * @param array<string, mixed> $block
     * @param array<string, mixed> $course
     * @return array<string, mixed>
     */
    private function block(array $block, array $course): array
    {
        $courseId = $this->normalizedId((string) ($course['courseId'] ?? ($block['courseId'] ?? '')));
        $professor = is_array($course['professor'] ?? null) ? $course['professor'] : [];

        return [
            'id' => (string) ($block['id'] ?? ''),
            'courseId' => $courseId,
            'courseCode' => (string) ($course['code'] ?? ''),
            'courseName' => (string) ($course['name'] ?? ''),
            'professor' => (string) ($professor['name'] ?? ''),
            'time' => (string) ($block['time'] ?? ''),
            'startTime' => (string) ($block['startTime'] ?? ''),
            'endTime' => (string) ($block['endTime'] ?? ''),
            'room' => (string) ($block['room'] ?? ($course['room'] ?? '')),
            'type' => (string) ($block['type'] ?? 'Lecture'),
            'status' => (string) ($block['status'] ?? 'scheduled'),
            'statusLabel' => (string) ($block['statusLabel'] ?? $this->statusLabel((string) ($block['status'] ?? 'scheduled'))),
            'tone' => (string) ($block['tone'] ?? $this->courseTone($courseId)),
        ];
    }

    /**
     * @param array<string, mixed> $attendance
     * @param array<string, array<string, mixed>> $courseLookup
     * @return list<array<string, mixed>>
     */
    private function history(array $attendance, array $courseLookup, ?string $selectedCourseId): array
    {
        $records = $attendance['history'] ?? [];

        if (!is_array($records) || !array_is_list($records)) {
            return [];
        }

        $history = [];

        foreach ($records as $record) {
            if (!is_array($record)) {
                continue;
            }

            $courseId = $this->normalizedId((string) ($record['courseId'] ?? ''));

            if ($courseId === '' || !isset($courseLookup[$courseId])) {
                continue;
            }

            if ($selectedCourseId !== null && $courseId !== $selectedCourseId) {
                continue;
            }

            $course = $courseLookup[$courseId];
            $professor = is_array($course['professor'] ?? null) ? $course['professor'] : [];
            $result = (string) ($record['result'] ?? 'present');

            $history[] = [
                'id' => (string) ($record['id'] ?? ''),
                'courseId' => $courseId,
                'courseCode' => (string) ($course['code'] ?? ''),
                'courseName' => (string) ($course['name'] ?? ''),
                'date' => (string) ($record['date'] ?? ''),
                'dateIso' => (string) ($record['dateIso'] ?? ''),
                'time' => (string) ($record['time'] ?? ''),
                'type' => (string) ($record['type'] ?? 'Lecture'),
                'professor' => (string) ($record['professor'] ?? ($professor['name'] ?? '')),
                'result' => $result,
                'resultLabel' => (string) ($record['resultLabel'] ?? $this->resultLabel($result)),
            ];
        }

        return $history;
    }

    /**
     * @param array<string, mixed> $attendance
     * @param array<string, array<string, mixed>> $courseLookup
     * @param list<array<string, mixed>> $history
     * @return array<string, mixed>|null
     */
    private function lastRecorded(
        array $attendance,
        array $courseLookup,
        array $history,
        ?string $selectedCourseId,
    ): ?array {
        $lastRecorded = is_array($attendance['lastRecorded'] ?? null) ? $attendance['lastRecorded'] : null;

        if ($lastRecorded !== null) {
            $courseId = $this->normalizedId((string) ($lastRecorded['courseId'] ?? ''));

            if ($courseId !== '' && isset($courseLookup[$courseId]) && ($selectedCourseId === null || $selectedCourseId === $courseId)) {
                return $this->recordedItem($lastRecorded, $courseLookup[$courseId]);
            }
        }

        if ($history === []) {
            return null;
        }

        $firstHistory = $history[0];

        return [
            'courseId' => (string) ($firstHistory['courseId'] ?? ''),
            'courseCode' => (string) ($firstHistory['courseCode'] ?? ''),
            'courseName' => (string) ($firstHistory['courseName'] ?? ''),
            'date' => (string) ($firstHistory['dateIso'] ?? ''),
            'dateLabel' => (string) ($firstHistory['date'] ?? ''),
            'time' => (string) ($firstHistory['time'] ?? ''),
            'status' => 'recorded',
            'statusLabel' => 'Recorded',
        ];
    }

    /**
     * @param array<string, mixed> $recorded
     * @param array<string, mixed> $course
     * @return array<string, mixed>
     */
    private function recordedItem(array $recorded, array $course): array
    {
        return [
            'courseId' => $this->normalizedId((string) ($recorded['courseId'] ?? ($course['courseId'] ?? ''))),
            'courseCode' => (string) ($course['code'] ?? ''),
            'courseName' => (string) ($course['name'] ?? ''),
            'date' => (string) ($recorded['date'] ?? ''),
            'dateLabel' => (string) ($recorded['dateLabel'] ?? ''),
            'time' => (string) ($recorded['time'] ?? ''),
            'status' => (string) ($recorded['status'] ?? 'recorded'),
            'statusLabel' => (string) ($recorded['statusLabel'] ?? 'Recorded'),
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $courseLookup
     * @return list<array<string, string>>
     */
    private function courseOptions(array $courseLookup): array
    {
        $options = [];

        foreach ($courseLookup as $courseId => $course) {
            $code = (string) ($course['code'] ?? '');
            $name = (string) ($course['name'] ?? '');

            $options[] = [
                'courseId' => $courseId,
                'code' => $code,
                'name' => $name,
                'label' => trim(sprintf('%s - %s', $code, $name), ' -'),
            ];
        }

        return $options;
    }

    /**
     * @param array<string, mixed> $studentCourses
     * @param array<string, array<string, mixed>> $courseLookup
     * @return list<string>
     */
    private function semesterOptions(array $studentCourses, array $courseLookup): array
    {
        $options = [];

        if (is_array($studentCourses['semesterOptions'] ?? null)) {
            foreach ($studentCourses['semesterOptions'] as $semester) {
                $options[] = (string) $semester;
            }
        }

        foreach ($courseLookup as $course) {
            $semester = (string) ($course['semester'] ?? '');

            if ($semester !== '') {
                $options[] = $semester;
            }
        }

        return array_values(array_unique(array_filter($options)));
    }

    private function percentage(int $value, int $total): int
    {
        if ($total <= 0) {
            return 0;
        }

        return (int) round(($value / $total) * 100);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'present' => 'Present',
            'absent' => 'Absent',
            'late' => 'Late',
            'recorded' => 'Recorded',
            default => 'Scheduled',
        };
    }

    private function resultLabel(string $result): string
    {
        return match ($result) {
            'absent' => 'Absent',
            'late' => 'Late',
            default => 'Present',
        };
    }

    private function courseTone(string $courseId): string
    {
        return match ($courseId) {
            'cs302' => 'purple',
            'cs306' => 'green',
            'cs308' => 'red',
            'cs310' => 'teal',
            'math204' => 'orange',
            default => 'blue',
        };
    }

    private function normalizedId(string $id): string
    {
        $normalized = preg_replace('/[^A-Za-z0-9_-]/', '-', strtolower(trim($id)));

        return $normalized ?? '';
    }
}
