<?php

namespace App\Http\Controllers\Academic;

use App\Http\Responses\ApiResponse;
use App\Services\StudentCoursesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class StudentCoursesController
{
    public function __construct(private StudentCoursesService $service) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success($this->service->overview($request));
    }

    public function show(Request $request, string $courseId): JsonResponse
    {
        $detail = $this->service->detail($request, $courseId);

        if ($detail === null) {
            return ApiResponse::error('Course not found or access denied.', status: 404);
        }

        return ApiResponse::success($detail);
    }
}
