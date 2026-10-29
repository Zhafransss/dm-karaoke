<?php

namespace App\Services;

use RuntimeException;

/** A booking rule or slot conflict, with a guest-friendly Indonesian message. */
class BookingException extends RuntimeException
{
    public function __construct(string $message, public bool $conflict = false)
    {
        parent::__construct($message);
    }
}
