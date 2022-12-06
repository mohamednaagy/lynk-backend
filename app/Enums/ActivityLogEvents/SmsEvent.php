<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * Event enum to be used with "Laravel-activitylog" package
 * This enum will be used with "app/Support/Sms"
 */
final class SmsEvent extends Enum
{
    const Sent = 'sms.sent';
}
