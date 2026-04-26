<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class StudentProfileUpdateData
{
    /**
     * @param array<string, string> $fields
     */
    public function __construct(private array $fields)
    {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $editableFields = [
            'fullName',
            'email',
            'phone',
            'address',
            'dateOfBirth',
            'gender',
            'nationality',
            'personalNumber',
            'emergencyContactName',
            'emergencyContactRelationship',
            'emergencyContactPhone',
        ];
        $fields = [];

        foreach ($editableFields as $field) {
            if (!array_key_exists($field, $payload)) {
                continue;
            }

            $fields[$field] = trim((string) $payload[$field]);
        }

        return new self($fields);
    }

    /**
     * @return array<string, string>
     */
    public function fields(): array
    {
        return $this->fields;
    }
}
