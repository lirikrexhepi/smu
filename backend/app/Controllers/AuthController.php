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

    
    public function login(Request $request, array $params = []): Response
    {
        $body = $request->body();
        $errors = $this->validator->validate($body);

        if ($errors !== []) {
            return Response::error('Validation failed', 422, $errors);
        }

        $result = $this->authService->attemptLogin(
            (string) ($body['identifier'] ?? ''),
            (string) ($body['password'] ?? ''),
        );

        if ($result === null) {
            return Response::error('Invalid credentials', 401, [
                'auth' => ['The credentials provided do not match our records.'],
            ]);
        }

        $this->sessions->login($result['user']);

    
        return Response::success([
            'user' => $result['user'],
            'role' => $result['user']['role'] ?? 'guest',
           
            'redirectTo' => ($result['user']['role'] === 'professor') ? '/professor/dashboard' : '/student/dashboard'
        ], 'Login successful');
    }



    public function logout(Request $request, array $params = []): Response
    {
        $this->sessions->logout();

        return Response::success(null, 'Logout successful');
    }

   
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
            'role' => $user['role'] ?? 'guest',
        ], 'Session active');
    }
}
