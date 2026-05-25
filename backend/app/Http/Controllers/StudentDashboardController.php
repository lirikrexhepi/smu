<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

final class StudentDashboardController
{
    public function show(): JsonResponse
    {
        return ApiResponse::success((object) []);
    }
}
