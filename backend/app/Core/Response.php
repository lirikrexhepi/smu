<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    /**
     * @param array<string, string> $headers
     * @param array<string, mixed>|null $payload
     */
    public function __construct(
        private readonly int $statusCode,
        private readonly ?array $payload = null,
        private array $headers = [],
    ) {
    }

    /**
     * @param array<string, mixed>|list<mixed>|null $data
     * @param array<string, mixed> $meta
     */
    public static function success(mixed $data = null, ?string $message = null, array $meta = [], int $status = 200): self
    {
        return new self($status, [
            'success' => true,
            'data' => $data,
            'message' => $message,
            'errors' => null,
            'meta' => (object) $meta,
        ]);
    }

    /**
     * @param array<string, mixed>|null $errors
     * @param array<string, mixed> $meta
     */
    public static function error(string $message, int $status = 400, ?array $errors = null, array $meta = []): self
    {
        return new self($status, [
            'success' => false,
            'data' => null,
            'message' => $message,
            'errors' => $errors,
            'meta' => (object) $meta,
        ]);
    }

    public static function empty(int $status = 204): self
    {
        return new self($status);
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;

        return $clone;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }

        if ($this->payload === null) {
            return;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return self::error($message, 401);
    }

    public static function validation(array $errors): self
    {
        return self::error('Validation failed', 422, $errors);
    }
}