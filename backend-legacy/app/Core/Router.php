<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /**
     * @var array<string, list<array{pattern: string, handler: callable, parameterNames: list<string>}>>
     */
    private array $routes = [];

    /**
     * @param callable(Request, array<string, string>): Response $handler
     */
    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    /**
     * @param callable(Request, array<string, string>): Response $handler
     */
    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    /**
     * @param callable(Request, array<string, string>): Response $handler
     */
    public function patch(string $path, callable $handler): void
    {
        $this->add('PATCH', $path, $handler);
    }

    public function dispatch(Request $request): Response
    {
        foreach ($this->routes[$request->method()] ?? [] as $route) {
            if (!preg_match($route['pattern'], $request->path(), $matches)) {
                continue;
            }

            $params = [];

            foreach ($route['parameterNames'] as $name) {
                $params[$name] = $matches[$name];
            }

            return ($route['handler'])($request, $params);
        }

        return Response::error('Route not found', 404);
    }

    /**
     * @param callable(Request, array<string, string>): Response $handler
     */
    private function add(string $method, string $path, callable $handler): void
    {
        [$pattern, $parameterNames] = $this->compilePath($path);

        $this->routes[$method][] = [
            'pattern' => $pattern,
            'handler' => $handler,
            'parameterNames' => $parameterNames,
        ];
    }

    /**
     * @return array{string, list<string>}
     */
    private function compilePath(string $path): array
    {
        $parameterNames = [];
        $pattern = preg_replace_callback(
            '/\{([A-Za-z_][A-Za-z0-9_]*)}/',
            static function (array $matches) use (&$parameterNames): string {
                $parameterNames[] = $matches[1];

                return sprintf('(?P<%s>[^/]+)', $matches[1]);
            },
            rtrim($path, '/') ?: '/',
        );

        return ['#^' . $pattern . '$#', $parameterNames];
    }
}
