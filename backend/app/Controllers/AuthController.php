<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;
use App\Services\SessionService;
use App\Validators\LoginRequestValidator;

final class AuthController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly LoginRequestValidator $validator,
        private readonly SessionService $sessions,
    ) {
    }

    /**
     * @param array<string, string> $params
     */
    public function login(Request $request, array $params = []): Response
    {
        $errors = $this->validator->validate($request->body());

        if ($errors !== []) {
            return Response::error('Validation failed', 422, $errors);
        }

        $result = $this->authService->attemptLogin(
            (string) $request->body()['identifier'],
            (string) $request->body()['password'],
        );

        if ($result === null) {
            return Response::error('Invalid email, ID, or password', 401, [
                'credentials' => ['The supplied credentials do not match a demo user.'],
            ]);
        }

        $this->sessions->login($result['user']);

        return Response::success($result, 'Login successful');
    }

    /**
     * @param array<string, string> $params
     */
    public function logout(Request $request, array $params = []): Response
    {
        $this->sessions->logout();

        return Response::success(null, 'Logout successful');
    }

    /**
     * @param array<string, string> $params
     */
    public function session(Request $request, array $params = []): Response
    {
        $user = $this->sessions->user();

        if ($user === null) {
            return Response::success([
                'authenticated' => false,
                'user' => null,
            ], 'No active session');
        }

        return Response::success([
            'authenticated' => true,
            'user' => $user,
        ], 'Session active');
    }
}
