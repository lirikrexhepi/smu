<?php

class JsonStorage {

    public static function read(string $path): array {
        if (!file_exists($path)) {
            return [];
        }

        $data = json_decode(file_get_contents($path), true);
        return $data ?? [];
    }

    public static function write(string $path, array $data): void {
        file_put_contents(
            $path,
            json_encode($data, JSON_PRETTY_PRINT),
            LOCK_EX
        );
    }
}