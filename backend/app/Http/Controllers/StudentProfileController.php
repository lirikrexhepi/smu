<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StudentProfileController
{
    public function show(): JsonResponse
    {
        return ApiResponse::success((object) []);
    }

    public function update(Request $request): JsonResponse
    {
        return ApiResponse::success($request->all(), 'Student profile update placeholder.');
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'avatarUrl' => null,
        ], 'Student avatar upload placeholder.');
    }
}
