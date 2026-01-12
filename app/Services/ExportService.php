<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\Reports\ExportOrderListJob;
use App\Models\User;
use Illuminate\Http\Request;

class ExportService
{
    /**
     * Dispatch an export job to be processed asynchronously via RabbitMQ
     */
    public function dispatchExportJob(
        string $sqlQuery,
        array $params,
        string $exportType,
        User $user,
        string $exportClass,
        Request $request,
    ): void {
        // Dispatch the job to RabbitMQ queue for processing by external service
        ExportOrderListJob::dispatch(
            userId: $user->id,
            exportType: $exportType,
            exportData: [
                'sql_query' => $sqlQuery,
                'query_params' => $params,
                'export_class' => $exportClass,
                'request_data' => $request->all(),
                'user_id' => $user->id,
                'export_type' => $exportType,
                'created_at' => now()->toISOString(),
            ]
        );
    }
}
