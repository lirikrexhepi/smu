<?php

declare(strict_types=1);

namespace App\Validators;

interface ValidatorInterface
{
    /**
     * @param array<string, mixed> $payload
     * @return array<string, list<string>>
     */
    public function validate(array $payload): array;
}
