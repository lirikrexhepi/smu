<?php

declare(strict_types=1);

namespace App\Services;

final class SessionService
{
    private const USER_KEY = 'auth_user';

    public function __construct(private readonly ?string $savePath = null)
    {
    }

    /**
     * @param array<string, mixed> $user
     */
    public function login(array $user): void
    {
        $this->start();
        session_regenerate_id(true);

        $_SESSION[self::USER_KEY] = $user;
    }

    public function hasUser(): bool
    {
        if (!$this->canReadSession()) {
            return false;
        }

        $this->start();

        return isset($_SESSION[self::USER_KEY]) && is_array($_SESSION[self::USER_KEY]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function user(): ?array
    {
        if (!$this->canReadSession()) {
            return null;
        }

        $this->start();
        $user = $_SESSION[self::USER_KEY] ?? null;

        return is_array($user) ? $user : null;
    }

    public function logout(): void
    {
        if (!$this->canReadSession()) {
            return;
        }

        $this->start();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly'],
            );
        }

        session_destroy();
    }

    private function canReadSession(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE || isset($_COOKIE[session_name()]);
    }

    private function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if ($this->savePath !== null) {
            if (!is_dir($this->savePath)) {
                mkdir($this->savePath, 0775, true);
            }

            session_save_path($this->savePath);
        }

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }
}
