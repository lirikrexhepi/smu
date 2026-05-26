<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StudentAttendanceController
{
    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'studentKey' => (string) ($request->user()?->public_id ?? ''),
            'semester' => '',
            'academicYear' => '',
            'selectedCourseId' => null,
            'selectedSemester' => '',
            'selectedWeek' => '',
            'filters' => [
                'courses' => [],
                'semesters' => [],
            ],
            'week' => [
                'startDate' => '',
                'endDate' => '',
                'label' => '',
                'requestedDate' => null,
            ],
            'summary' => [
                'overallAttendance' => 0,
                'presentSessions' => 0,
                'totalSessions' => 0,
                'absences' => 0,
                'lateRecords' => 0,
                'absenceRate' => 0,
                'lateRate' => 0,
                'comparisonVsLast4Weeks' => [
                    'value' => 0,
                    'direction' => 'flat',
                    'label' => 'No previous attendance data',
                ],
            ],
            'lastRecorded' => null,
            'weeklySchedule' => [],
            'history' => [],
        ]);
    }
}
