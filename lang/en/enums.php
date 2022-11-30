<?php

declare(strict_types=1);

use App\Enums\CompanyStatus;

return [
    CompanyStatus::class => [
        CompanyStatus::Pending => 'Pending',
        CompanyStatus::UnderReview => 'Under review',
        CompanyStatus::Approved => 'Approved',
        CompanyStatus::Approved => 'Rejected',
    ],
];
