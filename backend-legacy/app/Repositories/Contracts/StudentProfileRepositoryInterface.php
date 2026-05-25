<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Core\UploadedFile;
use App\DTO\StudentProfileData;
use App\DTO\StudentProfileUpdateData;

interface StudentProfileRepositoryInterface
{
    public function findByStudentKey(string $studentKey): StudentProfileData;

    public function update(string $studentKey, StudentProfileUpdateData $updateData): StudentProfileData;

    public function updateAvatar(string $studentKey, UploadedFile $avatar, string $mimeType): StudentProfileData;
}
