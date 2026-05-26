<?php

namespace App\Http\Controllers\Auth;

use App\Http\Responses\ApiResponse;
use App\Models\Identity\User;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

final readonly class AuthController
{
    public function __construct(private JwtService $jwt) {}

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error(
                'The given data was invalid.',
                $validator->errors()->toArray(),
                422
            );
        }

        $identifier = trim((string) $request->string('identifier'));
        $password = (string) $request->string('password');

        $user = User::query()
            ->with(['faculty', 'department'])
            ->where('email', $identifier)
            ->orWhere('institution_id', $identifier)
            ->first();

        if (! $user instanceof User || ! Hash::check($password, $user->password)) {
            return ApiResponse::error('Invalid credentials.', status: 401);
        }

        return ApiResponse::success([
            'token' => $this->jwt->issue($user),
            'user' => $user->toAuthUserArray(),
            'redirectPath' => $this->redirectPathFor($user->role),
        ]);
    }

    public function session(Request $request): JsonResponse
    {
        $claims = $this->jwt->validate($request->bearerToken() ?? '');

        if ($claims === null) {
            return ApiResponse::success([
                'authenticated' => false,
                'user' => null,
            ]);
        }

        $user = User::query()
            ->with(['faculty', 'department'])
            ->find($claims['sub']);

        if (! $user instanceof User || $user->public_id !== $claims['pid'] || $user->role !== $claims['role']) {
            return ApiResponse::success([
                'authenticated' => false,
                'user' => null,
            ]);
        }

        return ApiResponse::success([
            'authenticated' => true,
            'user' => $user->toAuthUserArray(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        return ApiResponse::success(null);
    }

    private function redirectPathFor(string $role): string
    {
        return match ($role) {
            'professor' => '/professor/dashboard',
            'admin' => '/admin/dashboard',
            default => '/student/dashboard',
        };
    }
}
