<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StudentGradesTranscriptController
{
    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'studentKey' => (string) ($request->user()?->public_id ?? ''),
            'academicYear' => '',
            'selectedSemester' => '',
            'selectedCourseId' => null,
            'filters' => [
                'semesters' => [],
                'courses' => [],
            ],
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
        ]);
    }
}
