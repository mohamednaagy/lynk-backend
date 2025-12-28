<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case PLATFORM = 'database';

    case MAIL = 'mail';
}
