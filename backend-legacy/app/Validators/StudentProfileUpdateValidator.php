<?php

declare(strict_types=1);

namespace App\Validators;

final class StudentProfileUpdateValidator implements ValidatorInterface
{
    private const MAX_LENGTH = 255;

    /**
     * @param array<string, mixed> $payload
     * @return array<string, list<string>>
     */
    public function validate(array $payload): array
    {
        $errors = [];

        // Normalize input (trim all strings)
        $payload = $this->normalize($payload);

        $requiredTextFields = [
            'fullName' => 'Full name',
            'email' => 'Email',
            'phone' => 'Phone',
            'address' => 'Address',
            'emergencyContactName' => 'Emergency contact name',
            'emergencyContactRelationship' => 'Emergency contact relationship',
            'emergencyContactPhone' => 'Emergency contact phone',
        ];

        // Required + length validation
        foreach ($requiredTextFields as $field => $label) {
            $value = $payload[$field] ?? null;

            if ($value === null || $value === '') {
                $errors[$field][] = $label . ' is required.';
                continue;
            }

            if ($this->exceedsMaxLength($value)) {
                $errors[$field][] = $label . ' must not exceed ' . self::MAX_LENGTH . ' characters.';
            }
        }

        // Email validation
        if (isset($payload['email']) && $payload['email'] !== '') {
            $email = strtolower($payload['email']);

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'][] = 'Email must be a valid email address.';
            }
        }

        // Phone validation
        foreach (['phone', 'emergencyContactPhone'] as $field) {
            if (!isset($payload[$field]) || $payload[$field] === '') {
                continue;
            }

            if (!$this->isValidPhone($payload[$field])) {
                $errors[$field][] = 'Phone number must be a valid international format.';
            }
        }

        // Date of birth validation
        if (isset($payload['dateOfBirth']) && $payload['dateOfBirth'] !== '') {
            $date = \DateTimeImmutable::createFromFormat('Y-m-d', $payload['dateOfBirth']);

            if ($date === false || $date->format('Y-m-d') !== $payload['dateOfBirth']) {
                $errors['dateOfBirth'][] = 'Date of birth must use YYYY-MM-DD format.';
            } else {
                $now = new \DateTimeImmutable();

                if ($date > $now) {
                    $errors['dateOfBirth'][] = 'Date of birth cannot be in the future.';
                }

                if ($date < $now->modify('-120 years')) {
                    $errors['dateOfBirth'][] = 'Date of birth is not realistic.';
                }
            }
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalize(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (is_string($value)) {
                $payload[$key] = trim($value);
            }
        }

        return $payload;
    }

    private function exceedsMaxLength(string $value): bool
    {
        return mb_strlen($value) > self::MAX_LENGTH;
    }

    private function isValidPhone(string $phone): bool
    {
        // Remove spaces and normalize
        $normalized = preg_replace('/\s+/', '', $phone);

        // Allow + and digits, enforce reasonable length
        return (bool) preg_match('/^\+?[0-9]{7,15}$/', $normalized);
    }
}
