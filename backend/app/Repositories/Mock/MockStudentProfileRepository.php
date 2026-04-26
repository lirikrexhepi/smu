<?php

declare(strict_types=1);

namespace App\Repositories\Mock;

use App\Core\UploadedFile;
use App\Data\MockData\SemsMockData;
use App\DTO\StudentProfileData;
use App\DTO\StudentProfileUpdateData;
use App\Repositories\Contracts\StudentProfileRepositoryInterface;

final class MockStudentProfileRepository implements StudentProfileRepositoryInterface
{
    private string $storageDirectory;
    private string $publicUploadDirectory;

    public function __construct(?string $storageDirectory = null, ?string $publicUploadDirectory = null)
    {
        $this->storageDirectory = $storageDirectory ?? dirname(__DIR__, 3) . '/storage/mock/profiles';
        $this->publicUploadDirectory = $publicUploadDirectory ?? dirname(__DIR__, 3) . '/public/uploads/profiles';
    }

    public function findByStudentKey(string $studentKey): StudentProfileData
    {
        return new StudentProfileData($this->readProfile($studentKey));
    }

    public function update(string $studentKey, StudentProfileUpdateData $updateData): StudentProfileData
    {
        $profile = $this->readProfile($studentKey);

        foreach ($updateData->fields() as $field => $value) {
            if (array_key_exists($field, $profile)) {
                $profile[$field] = $value;
                continue;
            }

            if (str_starts_with($field, 'emergencyContact')) {
                $emergencyField = lcfirst(substr($field, strlen('emergencyContact')));
                $profile['emergencyContact'][$emergencyField] = $value;
            }
        }

        $profile['initials'] = $this->initials((string) $profile['fullName']);
        $profile['updatedAt'] = gmdate('c');
        $this->writeProfile($studentKey, $profile);

        return new StudentProfileData($profile);
    }

    public function updateAvatar(string $studentKey, UploadedFile $avatar, string $mimeType): StudentProfileData
    {
        if (!is_dir($this->publicUploadDirectory)) {
            mkdir($this->publicUploadDirectory, 0775, true);
        }

        $safeKey = $this->safeStudentKey($studentKey);
        $extension = $this->extensionForMimeType($mimeType);
        $filename = $safeKey . '.' . $extension;
        $targetPath = $this->publicUploadDirectory . '/' . $filename;

        foreach (glob($this->publicUploadDirectory . '/' . $safeKey . '.*') ?: [] as $existingFile) {
            if (is_file($existingFile)) {
                unlink($existingFile);
            }
        }

        if (!move_uploaded_file($avatar->tmpName, $targetPath)) {
            rename($avatar->tmpName, $targetPath);
        }

        $profile = $this->readProfile($studentKey);
        $profile['avatarUrl'] = '/uploads/profiles/' . $filename . '?v=' . time();
        $profile['updatedAt'] = gmdate('c');
        $this->writeProfile($studentKey, $profile);

        return new StudentProfileData($profile);
    }

    /**
     * @return array<string, mixed>
     */
    private function readProfile(string $studentKey): array
    {
        $path = $this->profilePath($studentKey);

        if (is_file($path)) {
            $content = file_get_contents($path);
            $decoded = $content === false ? null : json_decode($content, true);

            if (is_array($decoded)) {
                return array_replace_recursive($this->defaultProfile($studentKey), $decoded);
            }
        }

        return $this->defaultProfile($studentKey);
    }

    /**
     * @param array<string, mixed> $profile
     */
    private function writeProfile(string $studentKey, array $profile): void
    {
        if (!is_dir($this->storageDirectory)) {
            mkdir($this->storageDirectory, 0775, true);
        }

        file_put_contents(
            $this->profilePath($studentKey),
            json_encode($profile, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );
    }

    private function profilePath(string $studentKey): string
    {
        return $this->storageDirectory . '/student-profile-' . $this->safeStudentKey($studentKey) . '.json';
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultProfile(string $studentKey): array
    {
        return SemsMockData::studentProfile($studentKey);
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = '';

        foreach ($parts as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }

        return substr($initials, 0, 2) ?: 'ST';
    }

    private function safeStudentKey(string $studentKey): string
    {
        return preg_replace('/[^A-Za-z0-9_-]/', '-', strtolower(trim($studentKey))) ?: 'unknown-student';
    }

    private function extensionForMimeType(string $mimeType): string
    {
        return match ($mimeType) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
    }
}
