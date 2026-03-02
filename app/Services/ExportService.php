<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\Reports\ExportReportJob;
use App\Models\User;

final class ExportService
{
    /**
     * Dispatch an export job to be processed asynchronously via RabbitMQ
     */
    public function dispatchReportExportJob(
        string $exportType,
        User $user,
        string $exportClass,
        string $fileName,
        array $requestData,
        ?string $locale = 'en',
    ): void {
        // Dispatch the job to RabbitMQ queue for processing by external service
        ExportReportJob::dispatch(
            userId: $user->id,
            exportType: $exportType,
            exportData: [
                'export_class' => $exportClass,
                'file_name' => $fileName,
                'request_data' => $requestData,
                'user_id' => $user->id,
                'export_type' => $exportType,
                'created_at' => now()->toISOString(),
            ],
            locale: $locale,
        );
    }
}
