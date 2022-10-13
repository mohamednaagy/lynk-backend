<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static Pending()
 * @method static static UnderReview()
 * @method static static Approved()
 * @method static static Rejected()
 */
final class CompanyStatus extends Enum
{
    const Pending = 1;
    const UnderReview = 2;
    const Approved = 3;
    const Rejected = 4;
}
