<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthController
{
    public function login(Request $request): JsonResponse
    {
        return ApiResponse::error('Authentication is not implemented in the Laravel foundation yet.', status: 501);
    }

    public function session(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'authenticated' => false,
            'user' => null,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return ApiResponse::success(null);
    }
}
