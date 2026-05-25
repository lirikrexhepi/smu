<?php

declare(strict_types=1);

namespace App\Validators;

use App\Core\UploadedFile;

final class StudentProfileAvatarValidator
{
    private const MAX_BYTES = 2_097_152;

    /**
     * @return array<string, list<string>>
     */
    public function validate(?UploadedFile $file): array
    {
        if ($file === null || $file->error === UPLOAD_ERR_NO_FILE) {
            return ['avatar' => ['Profile image is required.']];
        }

        if (!$file->isValid()) {
            return ['avatar' => ['Profile image upload failed.']];
        }

        if ($file->size > self::MAX_BYTES) {
            return ['avatar' => ['Profile image must be 2 MB or smaller.']];
        }

        $mimeType = $this->detectedMimeType($file);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($mimeType, $allowedTypes, true)) {
            return ['avatar' => ['Profile image must be a JPG, PNG, or WebP file.']];
        }

        if (getimagesize($file->tmpName) === false) {
            return ['avatar' => ['Profile image file is not readable as an image.']];
        }

        return [];
    }

    public function detectedMimeType(UploadedFile $file): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detected = $finfo === false ? false : finfo_file($finfo, $file->tmpName);

        if ($finfo !== false) {
            finfo_close($finfo);
        }

        return is_string($detected) ? $detected : $file->mimeType;
    }
}
