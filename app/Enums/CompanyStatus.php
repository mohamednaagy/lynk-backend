<?php

namespace App\Enums;

use BenSampo\Enum\Attributes\Description;
use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

/**
 * @method static static Pending()
 * @method static static UnderReview()
 * @method static static Approved()
 * @method static static Rejected()
 */
final class CompanyStatus extends Enum implements LocalizedEnum
{
    #[Description('Pending')]
    const Pending = 1;

    #[Description('UnderReview')]
    const UnderReview = 2;

    #[Description('Approved')]
    const Approved = 3;

    #[Description('Rejected')]
    const Rejected = 4;
}
