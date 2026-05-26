<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Services\StudentAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class StudentAttendanceController
{
    public function __construct(private StudentAttendanceService $attendance) {}

    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success($this->attendance->forRequest($request), 'Student attendance loaded');
    }
}
