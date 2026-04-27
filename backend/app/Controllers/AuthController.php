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
    private const COOKIE_NAME = 'user_token';
    private const COOKIE_SECRET = 'replace_this_with_a_long_secret_key';

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
            return Response::validation($errors);
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
        $this->issuePersonalizationCookie((string) $result['user']['id']);

        return Response::success($result, 'Login successful');
    }

    /**
     * Cookie personalization endpoint.
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

    public function logout(Request $request, array $params = []): Response
    {
        $this->sessions->logout();
        $this->clearPersonalizationCookie();

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

    private function issuePersonalizationCookie(string $userId): void
    {
        $token = base64_encode(
            $userId . '|' . hash_hmac('sha256', $userId, self::COOKIE_SECRET)
        );

        setcookie(self::COOKIE_NAME, $token, time() + (86400 * 7), '/', '', false, true);
    }

    private function clearPersonalizationCookie(): void
    {
        setcookie(self::COOKIE_NAME, '', time() - 3600, '/');
    }
}
