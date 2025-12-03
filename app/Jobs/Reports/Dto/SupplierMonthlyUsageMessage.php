<?php

namespace App\Jobs\Reports\Dto;

use App\Jobs\Reports\Enums\ReportType;
use App\Models\Company;
use Illuminate\Support\Str;

final class SupplierMonthlyUsageMessage extends AbstractReportMessage
{
    public function __construct(
        int $modelId,
        string $startDate,
        string $endDate,
    ) {
        parent::__construct(
            type: Str::upper(ReportType::SupplierMonthlyUsage),
            modelType: Company::class,
            modelId: $modelId,
            filters: [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        );
    }
}
