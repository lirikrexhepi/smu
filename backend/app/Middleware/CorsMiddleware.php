<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

final class CorsMiddleware implements MiddlewareInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(private readonly array $config)
    {
    }

    public function process(Request $request, callable $next): Response
    {
        $response = $request->method() === 'OPTIONS' ? Response::empty() : $next($request);

        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->allowedOrigin($request))
            ->withHeader('Access-Control-Allow-Methods', implode(', ', $this->config['allowed_methods'] ?? ['GET']))
            ->withHeader('Access-Control-Allow-Headers', implode(', ', $this->config['allowed_headers'] ?? ['Content-Type']))
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Vary', 'Origin');
    }

    private function allowedOrigin(Request $request): string
    {
        $origin = $request->header('origin');
        $allowedOrigins = $this->config['allowed_origins'] ?? [];

        if ($origin !== null && in_array($origin, $allowedOrigins, true)) {
            return $origin;
        }

        return $allowedOrigins[0] ?? '*';
    }
}
