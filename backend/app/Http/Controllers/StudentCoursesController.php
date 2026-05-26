<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StudentCoursesController
{
    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'semester' => '',
            'academicYear' => '',
            'summary' => [
                'enrolledCourses' => 0,
                'totalEcts' => 0,
                'ectsTarget' => 0,
                'upcomingDeadlines' => 0,
                'statusCounts' => [
                    'active' => 0,
                    'registered' => 0,
                    'upcoming' => 0,
                ],
                'gradeStats' => [
                    'average' => 0,
                    'min' => 0,
                    'max' => 0,
                ],
            ],
            'filters' => [
                'semesters' => [],
                'statuses' => [
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'registered', 'label' => 'Registered'],
                    ['value' => 'upcoming', 'label' => 'Upcoming'],
                ],
            ],
            'courses' => [],
            'upcomingDeadlines' => [],
        ]);
    }

    public function show(string $courseId): JsonResponse
    {
        return ApiResponse::success($this->emptyCourseDetail($courseId));
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyCourseDetail(string $courseId): array
    {
        return [
            'courseId' => $courseId,
            'code' => '',
            'name' => 'Course',
            'professor' => [
                'name' => '',
                'email' => '',
                'officeHours' => '',
                'consultation' => '',
            ],
            'ects' => 0,
            'schedule' => [
                'days' => '',
                'time' => '',
                'room' => '',
                'label' => '',
            ],
            'room' => '',
            'semester' => '',
            'status' => '',
            'description' => '',
            'overview' => [
                'learningOutcomes' => [],
                'topics' => [],
                'gradingBreakdown' => '',
            ],
            'courseInfo' => [],
            'materials' => [],
            'attendance' => [
                'percentage' => 0,
                'requiredPercentage' => 75,
                'sessionsHeld' => 0,
                'sessionsAttended' => 0,
                'status' => 'No attendance recorded',
                'summary' => [],
                'records' => [],
            ],
            'grades' => [
                'currentGrade' => '',
                'currentGradePoints' => '',
                'scale' => 'No grades published yet',
                'breakdown' => [],
                'records' => [],
            ],
            'assessments' => [],
            'exams' => [],
            'announcements' => [],
            'deadlines' => [],
            'enrollment' => [
                'status' => 'active',
                'statusLabel' => 'Active',
                'currentGrade' => '',
                'currentGradePoints' => '',
                'attendancePercentage' => 0,
                'nextImportantEventId' => '',
                'enrolledAt' => '',
            ],
        ];
    }
}
