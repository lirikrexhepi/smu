<?php

declare(strict_types=1);

namespace App\Validators;

final class StudentProfileUpdateValidator implements ValidatorInterface
{
    /**
     * @param array<string, mixed> $payload
     * @return array<string, list<string>>
     */
    public function validate(array $payload): array
    {
        $errors = [];
        $requiredTextFields = [
            'fullName' => 'Full name',
            'email' => 'Email',
            'phone' => 'Phone',
            'address' => 'Address',
            'emergencyContactName' => 'Emergency contact name',
            'emergencyContactRelationship' => 'Emergency contact relationship',
            'emergencyContactPhone' => 'Emergency contact phone',
        ];

        foreach ($requiredTextFields as $field => $label) {
            if (!array_key_exists($field, $payload)) {
                continue;
            }

            if (trim((string) $payload[$field]) === '') {
                $errors[$field][] = $label . ' is required.';
            }
        }

        if (isset($payload['email']) && !filter_var((string) $payload['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Email must be a valid email address.';
        }

        foreach (['phone', 'emergencyContactPhone'] as $field) {
            if (!isset($payload[$field])) {
                continue;
            }

            if (!preg_match('/^[0-9+()\-\s]{7,24}$/', (string) $payload[$field])) {
                $errors[$field][] = 'Phone number must use digits, spaces, plus, parentheses, or dashes.';
            }
        }

        if (isset($payload['dateOfBirth']) && trim((string) $payload['dateOfBirth']) !== '') {
            $date = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $payload['dateOfBirth']);

            if ($date === false || $date->format('Y-m-d') !== $payload['dateOfBirth']) {
                $errors['dateOfBirth'][] = 'Date of birth must use YYYY-MM-DD format.';
            }
        }

        return $errors;
    }
}
