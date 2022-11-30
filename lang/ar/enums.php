<?php

declare(strict_types=1);

use App\Enums\CompanyStatus;

return [
    CompanyStatus::class => [
        CompanyStatus::Pending => 'قيد الانتظار',
        CompanyStatus::UnderReview => 'قيد المراجعة',
        CompanyStatus::Approved => 'تمت الموافقة',
        CompanyStatus::Approved => 'تم الرفض',
    ],
];
