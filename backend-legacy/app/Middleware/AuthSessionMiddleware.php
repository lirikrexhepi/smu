<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\SessionService;

final class AuthSessionMiddleware implements MiddlewareInterface
{
    /**
     * @param list<string> $protectedPrefixes
     */
    public function __construct(
        private readonly SessionService $sessions,
        private readonly array $protectedPrefixes,
    ) {
    }

    public function process(Request $request, callable $next): Response
    {
        if ($this->isProtected($request->path()) && !$this->sessions->hasUser()) {
            return Response::error('Authentication required', 401, [
                'session' => ['Login is required to access this resource.'],
            ]);
        }

        return $next($request);
    }

    private function isProtected(string $path): bool
    {
        foreach ($this->protectedPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
