<?php

namespace App\Services\Sms;

use RuntimeException;

/**
 * Raised when the upstream SMS provider cannot deliver a message.
 *
 * The message is safe to expose to API clients: provider responses and
 * credentials must never leak through this exception.
 */
class SmsDeliveryException extends RuntimeException
{
}
