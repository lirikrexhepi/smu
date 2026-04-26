<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     * @param array<string, UploadedFile> $files
     * @param array<string, mixed> $server
     */
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $headers,
        private readonly array $query,
        private readonly array $body,
        private readonly array $files,
        private readonly array $server,
    ) {
    }

    public static function fromGlobals(): self
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $rawBody = file_get_contents('php://input') ?: '';
        $decodedBody = [];

        if ($rawBody !== '') {
            $json = json_decode($rawBody, true);
            $decodedBody = is_array($json) ? $json : [];
        }

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            '/' . trim($path, '/'),
            self::headersFromServer($_SERVER),
            $_GET,
            $decodedBody,
            self::filesFromGlobals($_FILES),
            $_SERVER,
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path === '/' ? '/' : rtrim($this->path, '/');
    }

    /**
     * @return array<string, mixed>
     */
    public function query(): array
    {
        return $this->query;
    }

    /**
     * @return array<string, mixed>
     */
    public function body(): array
    {
        return $this->body;
    }

    /**
     * @return array<string, UploadedFile>
     */
    public function files(): array
    {
        return $this->files;
    }

    public function file(string $name): ?UploadedFile
    {
        return $this->files[$name] ?? null;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function ip(): string
    {
        return (string) ($this->server['REMOTE_ADDR'] ?? '127.0.0.1');
    }

    /**
     * @param array<string, mixed> $server
     * @return array<string, string>
     */
    private static function headersFromServer(array $server): array
    {
        $headers = [];

        foreach ($server as $key => $value) {
            if (!str_starts_with($key, 'HTTP_')) {
                continue;
            }

            $name = strtolower(str_replace('_', '-', substr($key, 5)));
            $headers[$name] = (string) $value;
        }

        return $headers;
    }

    /**
     * @param array<string, mixed> $files
     * @return array<string, UploadedFile>
     */
    private static function filesFromGlobals(array $files): array
    {
        $uploadedFiles = [];

        foreach ($files as $fieldName => $file) {
            if (!is_array($file) || is_array($file['name'] ?? null)) {
                continue;
            }

            $uploadedFiles[$fieldName] = new UploadedFile(
                $fieldName,
                (string) ($file['name'] ?? ''),
                (string) ($file['type'] ?? 'application/octet-stream'),
                (string) ($file['tmp_name'] ?? ''),
                (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE),
                (int) ($file['size'] ?? 0),
            );
        }

        return $uploadedFiles;
    }
}
