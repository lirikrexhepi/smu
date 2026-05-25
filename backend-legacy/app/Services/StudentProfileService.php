<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\UploadedFile;
use App\DTO\StudentProfileData;
use App\DTO\StudentProfileUpdateData;
use App\Repositories\Contracts\StudentProfileRepositoryInterface;

final class StudentProfileService
{
    public function __construct(private readonly StudentProfileRepositoryInterface $profiles)
    {
    }

    public function profile(string $studentKey): StudentProfileData
    {
        return $this->profiles->findByStudentKey($studentKey);
    }

    public function updateProfile(string $studentKey, StudentProfileUpdateData $updateData): StudentProfileData
    {
        return $this->profiles->update($studentKey, $updateData);
    }

    public function updateAvatar(string $studentKey, UploadedFile $avatar, string $mimeType): StudentProfileData
    {
        return $this->profiles->updateAvatar($studentKey, $avatar, $mimeType);
    }
}
