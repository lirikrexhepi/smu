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

        if (isset($payload['identifier']) && trim((string) $payload['identifier']) !== '') {
            $identifier = trim((string) $payload['identifier']);

            if (
                !filter_var($identifier, FILTER_VALIDATE_EMAIL)
                && !preg_match('/^[a-zA-Z0-9._-]{3,30}$/', $identifier)
            ) {
                $errors['identifier'][] = 'Identifier must be a valid email or student ID.';
            }
        }

        if (!isset($payload['password']) || (string) $payload['password'] === '') {
            $errors['password'][] = 'Password is required.';
        }

        if (isset($payload['password']) && (string) $payload['password'] !== '') {
            $password = (string) $payload['password'];

            if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
                $errors['password'][] = 'Password must be at least 8 characters and contain uppercase, lowercase, number, and special character.';
            }
        }

        return $errors;
    }
}