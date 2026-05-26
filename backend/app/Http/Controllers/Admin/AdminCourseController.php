<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\CreateCourseRequest;
use App\Http\Requests\Admin\UpdateCourseRequest;
use App\Http\Responses\ApiResponse;
use App\Services\AdminCourseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class AdminCourseController
{
    public function __construct(private AdminCourseService $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'department_id', 'semester_id']);
        $data = $this->service->listCourses($filters);
        return ApiResponse::success($data, 'Courses retrieved successfully.');
    }

    public function show(int $id): JsonResponse
    {
        $data = $this->service->getCourse($id);
        return ApiResponse::success($data, 'Course details retrieved successfully.');
    }

    public function store(CreateCourseRequest $request): JsonResponse
    {
        $course = $this->service->createCourse($request->validated());
        return ApiResponse::success($course, 'Course created successfully.', status: 201);
    }

    public function update(UpdateCourseRequest $request, int $id): JsonResponse
    {
        $course = $this->service->updateCourse($id, $request->validated());
        return ApiResponse::success($course, 'Course updated successfully.');
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->deleteCourse($id);
        return ApiResponse::success(null, 'Course deleted successfully.');
    }
}
