<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Services\StudentCoursesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class StudentCoursesController
{
    public function __construct(private StudentCoursesService $courses) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success($this->courses->overview($request), 'Student courses loaded');
    }

    public function show(Request $request, string $courseId): JsonResponse
    {
        $course = $this->courses->detail($request, $courseId);

        if ($course === null) {
            return ApiResponse::error('Course not found.', status: 404);
        }

        return ApiResponse::success($course, 'Student course loaded');
    }
}
