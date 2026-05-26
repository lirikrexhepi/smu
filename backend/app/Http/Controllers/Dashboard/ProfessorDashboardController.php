<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Responses\ApiResponse;
use App\Services\Dashboard\ProfessorDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class ProfessorDashboardController
{
    public function __construct(
        private ProfessorDashboardService $service
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null || $user->role !== 'professor') {
            return ApiResponse::error('Unauthorized access.', status: 403);
        }

        $data = $this->service->getDashboardData($user);

        return ApiResponse::success($data, 'Professor dashboard loaded successfully.');
    }
}
