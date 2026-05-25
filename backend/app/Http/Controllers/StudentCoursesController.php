<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

final class StudentCoursesController
{
    public function index(): JsonResponse
    {
        return ApiResponse::success((object) []);
    }

    public function show(string $courseId): JsonResponse
    {
        return ApiResponse::success([
            'courseId' => $courseId,
        ]);
    }
}
