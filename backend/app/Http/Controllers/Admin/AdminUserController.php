<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\CreateUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Responses\ApiResponse;
use App\Services\AdminUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class AdminUserController
{
    public function __construct(private AdminUserService $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'role', 'faculty_id', 'department_id']);
        $data = $this->service->listUsers($filters);
        return ApiResponse::success($data, 'Users retrieved successfully.');
    }

    public function show(int $id): JsonResponse
    {
        $data = $this->service->getUser($id);
        return ApiResponse::success($data, 'User details retrieved successfully.');
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        $user = $this->service->createUser($request->validated());
        return ApiResponse::success($user, 'User created successfully.', status: 201);
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = $this->service->updateUser($id, $request->validated());
        return ApiResponse::success($user, 'User updated successfully.');
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->deleteUser($id);
        return ApiResponse::success(null, 'User deleted successfully.');
    }
}
