<?php

declare(strict_types=1);

namespace App\Jobs\Reports\Dto;

use App\Models\User;
use Illuminate\Support\Str;

final class ExportReportMessage extends AbstractReportMessage
{
    public function __construct(
        int $modelId,
        string $exportType,
        array $exportData = [],
        string $outputTimezone = 'Asia/Riyadh',
        string $outputLanguage = 'en',
    ) {
        parent::__construct(
            type: Str::upper($exportType),
            modelType: User::class,
            modelId: $modelId,
            filters: [
                'export_type' => $exportType,
                'export_data' => $exportData,
            ],
            options: [
                'language' => $outputLanguage,
                'output_timezone' => $outputTimezone,
                'rows_limit' => config('reports.orders_export_limit'),
            ],
        );
    }
}
