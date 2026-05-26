<?php

namespace App\Http\Controllers\Academic;

use App\Http\Responses\ApiResponse;
use App\Services\Academic\ProfessorCoursesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class ProfessorCoursesController
{
    public function __construct(
        private ProfessorCoursesService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null || $user->role !== 'professor') {
            return ApiResponse::error('Unauthorized access.', status: 403);
        }

        $data = $this->service->getCourses($user);

        return ApiResponse::success($data, 'Professor courses loaded successfully.');
    }
}
