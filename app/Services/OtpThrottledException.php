<?php

namespace App\Services;

use RuntimeException;

/**
 * Raised when an OTP request or verification is temporarily blocked by a
 * server-side limit. Clients may use retryAfterSeconds only for UI countdowns;
 * the backend always remains authoritative.
 */
class OtpThrottledException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $retryAfterSeconds = null,
    ) {
        parent::__construct($message);
    }
}
