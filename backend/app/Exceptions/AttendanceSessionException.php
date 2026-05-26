<?php

namespace App\Exceptions;

use RuntimeException;

final class AttendanceSessionException extends RuntimeException
{
    public function __construct(string $message, private readonly int $status = 400)
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }
}
