<?php

declare(strict_types=1);

namespace App\Data\MockData;

use RuntimeException;

final class SemsMockData
{
    /**
     * @return list<array{
     *   id: string,
     *   role: string,
     *   institutionId: string,
     *   password: string,
     *   name?: string,
     *   email?: string,
     *   faculty?: string,
     *   department?: string
     * }>
     */
    public static function users(): array
    {
        $users = self::readJson(__DIR__ . '/users.json');

        if (!array_is_list($users)) {
            throw new RuntimeException('Mock users data must be a JSON list.');
        }

        return $users;
    }

    /**
     * @return array<string, mixed>
     */
    public static function studentProfile(string $studentKey): array
    {
        return self::readStudentJson($studentKey, 'profile.json');
    }

    /**
     * @return array<string, mixed>
     */
    public static function studentDashboard(string $studentKey): array
    {
        return self::readStudentJson($studentKey, 'dashboard.json');
    }

    /**
     * @return array<string, mixed>
     */
    public static function studentCourses(string $studentKey): array
    {
        return self::readStudentJson($studentKey, 'courses.json');
    }

    /**
     * @return array<string, mixed>
     */
    public static function course(string $courseId): array
    {
        return self::readJson(__DIR__ . '/courses/' . self::safeCourseId($courseId) . '.json');
    }

    /**
     * @return array<string, mixed>
     */
    private static function readStudentJson(string $studentKey, string $filename): array
    {
        return self::readJson(__DIR__ . '/students/' . self::safeStudentKey($studentKey) . '/' . $filename);
    }

    /**
     * @return array<string, mixed>
     */
    private static function readJson(string $path): array
    {
        if (!is_file($path)) {
            throw new RuntimeException(sprintf('Mock data file not found: %s', $path));
        }

        $content = file_get_contents($path);

        if ($content === false) {
            throw new RuntimeException(sprintf('Unable to read mock data file: %s', $path));
        }

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            throw new RuntimeException(sprintf('Mock data file must decode to an array: %s', $path));
        }

        return $decoded;
    }

    private static function safeStudentKey(string $studentKey): string
    {
        $safeKey = preg_replace('/[^A-Za-z0-9_-]/', '-', strtolower(trim($studentKey)));

        if ($safeKey === null || $safeKey === '') {
            throw new RuntimeException('Student key is required.');
        }

        return $safeKey;
    }

    private static function safeCourseId(string $courseId): string
    {
        $safeId = preg_replace('/[^A-Za-z0-9_-]/', '-', strtolower(trim($courseId)));

        if ($safeId === null || $safeId === '') {
            throw new RuntimeException('Course id is required.');
        }

        return $safeId;
    }
}
