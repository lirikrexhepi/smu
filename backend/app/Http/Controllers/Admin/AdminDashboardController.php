<?php

namespace App\Http\Controllers\Admin;

use App\Http\Responses\ApiResponse;
use App\Services\AdminDashboardService;
use Illuminate\Http\JsonResponse;

final readonly class AdminDashboardController
{
    public function __construct(private AdminDashboardService $service) {}

    public function show(): JsonResponse
    {
        return ApiResponse::success($this->service->getDashboardData(), 'Admin dashboard data loaded');
    }
}
