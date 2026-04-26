<?php

declare(strict_types=1);

namespace App\Repositories\Mock;

use App\Data\MockData\SemsMockData;
use App\DTO\StudentCourseDetailData;
use App\DTO\StudentCoursesOverviewData;
use App\Repositories\Contracts\StudentCoursesRepositoryInterface;
use RuntimeException;

final class MockStudentCoursesRepository implements StudentCoursesRepositoryInterface
{
    public function listForStudent(string $studentKey): StudentCoursesOverviewData
    {
        $studentCourses = SemsMockData::studentCourses($studentKey);
        $enrollments = $this->enrollments($studentCourses);

        $courses = [];
        $upcomingDeadlines = [];
        $totalEcts = 0;

        foreach ($enrollments as $enrollment) {
            $course = SemsMockData::course((string) $enrollment['courseId']);
            $overviewCourse = $this->overviewCourse($course, $enrollment);

            $courses[] = $overviewCourse;
            $totalEcts += (int) ($overviewCourse['ects'] ?? 0);

            if (is_array($overviewCourse['nextImportantEvent'] ?? null) && $this->isUpcomingDeadline($overviewCourse['nextImportantEvent'])) {
                $upcomingDeadlines[] = $this->deadlineSummary($course, $overviewCourse['nextImportantEvent']);
            }
        }

        return new StudentCoursesOverviewData([
            'semester' => (string) ($studentCourses['semester'] ?? ''),
            'academicYear' => (string) ($studentCourses['academicYear'] ?? ''),
            'summary' => [
                'enrolledCourses' => count($courses),
                'totalEcts' => $totalEcts,
                'ectsTarget' => (int) ($studentCourses['ectsTarget'] ?? $totalEcts),
                'upcomingDeadlines' => count($upcomingDeadlines),
            ],
            'filters' => [
                'semesters' => $this->semesterOptions($studentCourses, $courses),
                'statuses' => $this->statusOptions($courses),
            ],
            'courses' => $courses,
            'upcomingDeadlines' => array_slice($upcomingDeadlines, 0, 6),
        ]);
    }

    public function findForStudent(string $studentKey, string $courseId): StudentCourseDetailData
    {
        $studentCourses = SemsMockData::studentCourses($studentKey);
        $enrollment = $this->findEnrollment($this->enrollments($studentCourses), $courseId);

        if ($enrollment === null) {
            throw new RuntimeException(sprintf('Student is not enrolled in course: %s', $courseId));
        }

        $course = SemsMockData::course((string) $enrollment['courseId']);
        $course['schedule'] = $this->normalizedSchedule($course);
        $course['enrollment'] = [
            'status' => (string) ($enrollment['enrollmentStatus'] ?? 'active'),
            'statusLabel' => (string) ($enrollment['enrollmentStatusLabel'] ?? $this->statusLabel((string) ($enrollment['enrollmentStatus'] ?? 'active'))),
            'currentGrade' => (string) ($enrollment['currentGrade'] ?? ''),
            'currentGradePoints' => (string) ($enrollment['currentGrade'] ?? ''),
            'attendancePercentage' => (int) ($enrollment['attendancePercentage'] ?? 0),
            'nextImportantEventId' => (string) ($enrollment['nextImportantEventId'] ?? ''),
            'enrolledAt' => (string) ($enrollment['enrolledAt'] ?? ''),
        ];

        $course['attendance'] = $this->attendanceWithEnrollment($course['attendance'] ?? [], $enrollment);
        $course['grades'] = $this->gradesWithEnrollment($course['grades'] ?? [], $enrollment);

        return new StudentCourseDetailData($course);
    }

    /**
     * @param array<string, mixed> $studentCourses
     * @return list<array<string, mixed>>
     */
    private function enrollments(array $studentCourses): array
    {
        $enrollments = $studentCourses['courses'] ?? [];

        if (!is_array($enrollments) || !array_is_list($enrollments)) {
            throw new RuntimeException('Student courses mock data must contain a courses list.');
        }

        return $enrollments;
    }

    /**
     * @param array<string, mixed> $course
     * @param array<string, mixed> $enrollment
     * @return array<string, mixed>
     */
    private function overviewCourse(array $course, array $enrollment): array
    {
        $schedule = is_array($course['schedule'] ?? null) ? $course['schedule'] : [];
        $professor = is_array($course['professor'] ?? null) ? $course['professor'] : [];
        $status = (string) ($enrollment['enrollmentStatus'] ?? 'active');

        return [
            'courseId' => (string) ($course['courseId'] ?? $enrollment['courseId']),
            'code' => (string) ($course['code'] ?? ''),
            'name' => (string) ($course['name'] ?? ''),
            'professor' => (string) ($professor['name'] ?? ''),
            'ects' => (int) ($course['ects'] ?? 0),
            'schedule' => [
                'days' => (string) ($schedule['daysLabel'] ?? ''),
                'time' => (string) ($schedule['time'] ?? ''),
                'room' => (string) ($schedule['room'] ?? ($course['room'] ?? '')),
                'label' => (string) ($schedule['label'] ?? ''),
            ],
            'room' => (string) ($course['room'] ?? ($schedule['room'] ?? '')),
            'semester' => (string) ($course['semester'] ?? ''),
            'enrollmentStatus' => $status,
            'enrollmentStatusLabel' => (string) ($enrollment['enrollmentStatusLabel'] ?? $this->statusLabel($status)),
            'currentGrade' => (string) ($enrollment['currentGrade'] ?? ''),
            'currentGradePoints' => (string) ($enrollment['currentGrade'] ?? ''),
            'attendancePercentage' => (int) ($enrollment['attendancePercentage'] ?? 0),
            'nextImportantEvent' => $this->resolveEvent($course, (string) ($enrollment['nextImportantEventId'] ?? '')),
        ];
    }

    /**
     * @param array<string, mixed> $course
     * @return array<string, string>
     */
    private function normalizedSchedule(array $course): array
    {
        $schedule = is_array($course['schedule'] ?? null) ? $course['schedule'] : [];

        return [
            'days' => (string) ($schedule['daysLabel'] ?? ''),
            'time' => (string) ($schedule['time'] ?? ''),
            'room' => (string) ($schedule['room'] ?? ($course['room'] ?? '')),
            'label' => (string) ($schedule['label'] ?? ''),
        ];
    }

    /**
     * @param list<array<string, mixed>> $enrollments
     * @return array<string, mixed>|null
     */
    private function findEnrollment(array $enrollments, string $courseId): ?array
    {
        $needle = $this->normalizedId($courseId);

        foreach ($enrollments as $enrollment) {
            if ($this->normalizedId((string) ($enrollment['courseId'] ?? '')) === $needle) {
                return $enrollment;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $course
     * @return array<string, mixed>|null
     */
    private function resolveEvent(array $course, string $eventId): ?array
    {
        if (trim($eventId) === '') {
            return null;
        }

        foreach (['deadlines', 'assessments', 'exams'] as $section) {
            $events = $course[$section] ?? [];

            if (!is_array($events)) {
                continue;
            }

            foreach ($events as $event) {
                if (!is_array($event) || (string) ($event['id'] ?? '') !== $eventId) {
                    continue;
                }

                return $this->eventSummary($event, $section);
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $event
     * @return array<string, mixed>
     */
    private function eventSummary(array $event, string $section): array
    {
        return [
            'id' => (string) ($event['id'] ?? ''),
            'title' => (string) ($event['title'] ?? ''),
            'type' => (string) ($event['type'] ?? $this->sectionLabel($section)),
            'date' => (string) ($event['date'] ?? ''),
            'time' => (string) ($event['time'] ?? ''),
            'statusLabel' => (string) ($event['statusLabel'] ?? ''),
            'tone' => (string) ($event['tone'] ?? 'blue'),
        ];
    }

    /**
     * @param array<string, mixed> $course
     * @param array<string, mixed> $event
     * @return array<string, mixed>
     */
    private function deadlineSummary(array $course, array $event): array
    {
        return [
            ...$event,
            'courseId' => (string) ($course['courseId'] ?? ''),
            'courseCode' => (string) ($course['code'] ?? ''),
            'courseName' => (string) ($course['name'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $event
     */
    private function isUpcomingDeadline(array $event): bool
    {
        if (strtolower((string) ($event['type'] ?? '')) === 'lab') {
            return false;
        }

        $statusLabel = (string) ($event['statusLabel'] ?? '');

        if ($statusLabel === 'Today') {
            return true;
        }

        if (preg_match('/^(\d+) days left$/', $statusLabel, $matches) !== 1) {
            return false;
        }

        return (int) $matches[1] <= 14;
    }

    /**
     * @param array<string, mixed> $studentCourses
     * @param list<array<string, mixed>> $courses
     * @return list<string>
     */
    private function semesterOptions(array $studentCourses, array $courses): array
    {
        $options = [];

        if (is_array($studentCourses['semesterOptions'] ?? null)) {
            foreach ($studentCourses['semesterOptions'] as $semester) {
                $options[] = (string) $semester;
            }
        }

        foreach ($courses as $course) {
            $options[] = (string) ($course['semester'] ?? '');
        }

        return array_values(array_unique(array_filter($options)));
    }

    /**
     * @param list<array<string, mixed>> $courses
     * @return list<array{value: string, label: string}>
     */
    private function statusOptions(array $courses): array
    {
        $statuses = [];

        foreach ($courses as $course) {
            $value = (string) ($course['enrollmentStatus'] ?? '');

            if ($value === '') {
                continue;
            }

            $statuses[$value] = [
                'value' => $value,
                'label' => (string) ($course['enrollmentStatusLabel'] ?? $this->statusLabel($value)),
            ];
        }

        return array_values($statuses);
    }

    /**
     * @param array<string, mixed> $attendance
     * @param array<string, mixed> $enrollment
     * @return array<string, mixed>
     */
    private function attendanceWithEnrollment(array $attendance, array $enrollment): array
    {
        $attendance['percentage'] = (int) ($enrollment['attendancePercentage'] ?? ($attendance['percentage'] ?? 0));

        return $attendance;
    }

    /**
     * @param array<string, mixed> $grades
     * @param array<string, mixed> $enrollment
     * @return array<string, mixed>
     */
    private function gradesWithEnrollment(array $grades, array $enrollment): array
    {
        $grades['currentGrade'] = (string) ($enrollment['currentGrade'] ?? ($grades['currentGrade'] ?? ''));
        $grades['currentGradePoints'] = (string) ($enrollment['currentGrade'] ?? ($grades['currentGradePoints'] ?? ''));

        return $grades;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'registered' => 'Registered',
            'upcoming' => 'Upcoming',
            default => 'Active',
        };
    }

    private function sectionLabel(string $section): string
    {
        return match ($section) {
            'assessments' => 'Assessment',
            'exams' => 'Exam',
            default => 'Deadline',
        };
    }

    private function normalizedId(string $id): string
    {
        $normalized = preg_replace('/[^A-Za-z0-9_-]/', '-', strtolower(trim($id)));

        return $normalized ?? '';
    }
}
