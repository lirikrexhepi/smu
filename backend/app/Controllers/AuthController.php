<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;
use App\Validators\LoginRequestValidator;

final class AuthController
{
    private const COOKIE_NAME = 'user_token';
    private const COOKIE_SECRET = 'replace_this_with_a_long_secret_key';

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
            return Response::validation($errors);
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

        // ADDED: create signed cookie after successful login
        $userId = (string) $result['user']['id'];

        $token = base64_encode(
            $userId . '|' . hash_hmac('sha256', $userId, self::COOKIE_SECRET)
        );

        setcookie(
            self::COOKIE_NAME,
            $token,
            time() + (86400 * 7),
            '/',
            '',
            false,
            true
        );

        return Response::success($result, 'Login successful');
    }

    /**
     * ADDED: cookie personalization endpoint
     *
     * @param array<string, string> $params
     */
    public function me(Request $request, array $params = []): Response
    {
        $userId = $request->getAuthenticatedUserId();

        if ($userId === null) {
            return Response::unauthorized();
        }

        return Response::success([
            'userId' => $userId,
            'message' => "Welcome back, user {$userId}.",
        ], 'Personalized user data loaded');
    }

    /**
     * ADDED: logout clears cookie
     *
     * @param array<string, string> $params
     */
    public function logout(Request $request, array $params = []): Response
    {
        setcookie(self::COOKIE_NAME, '', time() - 3600, '/');

        return Response::success(null, 'Logged out successfully');
    }
}