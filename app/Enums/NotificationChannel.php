<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class NotificationChannel extends Enum
{
    const PLATFORM = 'database';

    const MAIL = 'mail';
}
