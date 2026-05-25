<?php

declare(strict_types=1);

namespace App\Core;

final readonly class UploadedFile
{
    public function __construct(
        public string $fieldName,
        public string $originalName,
        public string $mimeType,
        public string $tmpName,
        public int $error,
        public int $size,
    ) {
    }

    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK && $this->tmpName !== '' && is_file($this->tmpName);
    }
}
