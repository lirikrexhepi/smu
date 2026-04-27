<?php

declare(strict_types=1);

namespace App\Services;

final class SessionService
{
    private const USER_KEY = 'auth_user';

    public function __construct(private readonly ?string $savePath = null)
    {
    }

  
    public function login(array $user): void
    {
        $this->start();
        
        // Prevents Session Fixation attacks
        session_regenerate_id(true);

        $_SESSION[self::USER_KEY] = $user;
    }

    /**
     * Kthen te dhenat e perdoruesit nga sesioni
    
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

    /**
     * Ndihmon qe te merr rolin e perdoruesit menjher(student,profesor).
     */
    public function role(): ?string
    {
        $user = $this->user();
        return $user['role'] ?? null;
    }

    
    public function hasRole(string $role): bool
    {
        return $this->role() === $role;
    }

    public function hasUser(): bool
    {
        return $this->user() !== null;
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
            'lifetime' => 0, // Sesioni perfundon kur mbyllet browseri
            'path' => '/',
            'secure' => false, // nesee perdoret https behet "true"
            'httponly' => true, // nuk e lejon Js te ket acces ne cookies(pra largimi nga varja e local storage sa me shume)
            'samesite' => 'Lax',
        ]);

        session_start();
    }
}
