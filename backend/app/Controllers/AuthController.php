<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;
use App\Validators\LoginRequestValidator;

final class AuthController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly LoginRequestValidator $validator,
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

        return Response::success($result, 'Login successful');
    }
}
