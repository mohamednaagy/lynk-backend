<?php

declare(strict_types=1);

namespace App\Jobs\Reports\Dto;

use App\Jobs\Reports\Enums\ReportType;
use App\Models\User;
use Illuminate\Support\Str;

final class OrderListMessage extends AbstractReportMessage
{
    public function __construct(
        int $modelId,
        string $exportType,
        array $exportData = [],
        string $outputTimezone = 'Asia/Riyadh',
    ) {
        parent::__construct(
            type: Str::upper(ReportType::OrderList),
            modelType: User::class,
            modelId: $modelId,
            filters: [
                'export_type' => $exportType,
                'export_data' => $exportData,
            ],
            options: [
                'output_timezone' => $outputTimezone,
                'rows_limit' => (int) env('ORDERS_EXPORT_LIMIT', 300000),
            ],
        );
    }
}
