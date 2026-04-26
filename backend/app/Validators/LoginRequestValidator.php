<?php

declare(strict_types=1);

namespace App\Validators;

final class LoginRequestValidator implements ValidatorInterface
{
    public function validate(array $payload): array
    {
        $errors = [];

        if (!isset($payload['identifier']) || trim((string) $payload['identifier']) === '') {
            $errors['identifier'][] = 'Email or ID is required.';
        }

        if (!isset($payload['password']) || (string) $payload['password'] === '') {
            $errors['password'][] = 'Password is required.';
        }

        return $errors;
    }
}
