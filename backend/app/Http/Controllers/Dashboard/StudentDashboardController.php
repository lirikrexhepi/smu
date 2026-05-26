<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Responses\ApiResponse;
use App\Services\StudentDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class StudentDashboardController
{
    public function __construct(private StudentDashboardService $service) {}

    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->service->forRequest($request),
            'Student dashboard loaded'
        );
    }
}
