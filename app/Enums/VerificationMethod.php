<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class VerificationMethod extends Enum
{
    const Email = 'email';

    const Phone = 'phone';
}
