<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\SessionService;

final class RoleGuardMiddleware implements MiddlewareInterface
{
    /**
     * @param array<string, list<string>> $rolePrefixes
     */
    public function __construct(
        private readonly SessionService $sessions,
        private readonly array $rolePrefixes,
    ) {
    }

    public function process(Request $request, callable $next): Response
    {
        foreach ($this->rolePrefixes as $prefix => $roles) {
            if (!$this->matchesPrefix($request->path(), $prefix)) {
                continue;
            }

            if (!$this->hasAllowedRole($roles)) {
                return Response::error('Forbidden', 403, [
                    'role' => ['Your account cannot access this resource.'],
                ]);
            }

            break;
        }

        return $next($request);
    }

    /**
     * @param list<string> $roles
     */
    private function hasAllowedRole(array $roles): bool
    {
        $role = $this->sessions->role();

        return $role !== null && in_array($role, $roles, true);
    }

    private function matchesPrefix(string $path, string $prefix): bool
    {
        $prefix = rtrim($prefix, '/');

        return $path === $prefix || str_starts_with($path, $prefix . '/');
    }
}
