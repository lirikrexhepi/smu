<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

final class HealthController
{
    public function show(): JsonResponse
    {
        return ApiResponse::success([
            'status' => 'ok',
            'service' => 'sems-laravel-api',
        ]);
    }
}
