<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\Reports\ExportOrderListJob;
use App\Jobs\Reports\ExportTransactionListJob;
use App\Models\User;

class ExportService
{
    /**
     * Dispatch an export job to be processed asynchronously via RabbitMQ
     */
    public function dispatchOrderListExportJob(
        string $exportType,
        User $user,
        string $exportClass,
        string $fileName,
        array $requestData,
    ): void {
        // Dispatch the job to RabbitMQ queue for processing by external service
        ExportOrderListJob::dispatch(
            userId: $user->id,
            exportType: $exportType,
            exportData: [
                'export_class' => $exportClass,
                'file_name' => $fileName,
                'request_data' => $requestData,
                'user_id' => $user->id,
                'export_type' => $exportType,
                'created_at' => now()->toISOString(),
            ]
        );
    }

    public function dispatchTransactionListExportJob(
        string $exportType,
        User $user,
        string $exportClass,
        string $fileName,
        array $requestData,
        ?string $locale = 'en',
    ): void {
        // Dispatch the job to RabbitMQ queue for processing by external service
        ExportTransactionListJob::dispatch(
            userId: $user->id,
            exportType: $exportType,
            exportData: [
                'export_class' => $exportClass,
                'file_name' => $fileName,
                'request_data' => $requestData,
                'created_at' => now()->toISOString(),
            ],
            locale: $locale,
        );
    }
}
