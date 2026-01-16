<?php

declare(strict_types=1);

namespace App\Jobs\Reports;

use App\Jobs\Reports\Dto\OrderListMessage;
use App\Jobs\Reports\Dto\ReportMessage;
use Throwable;

class ExportOrderListJob extends BaseReportJob
{
    public function __construct(
        public int $userId,
        public string $exportType,
        public array $exportData = []
    ) {
        parent::__construct();
    }

    /**
     * Get the context for logging job failures
     */
    protected function getLogContext(?Throwable $exception): array
    {
        return array_merge(parent::getLogContext($exception), [
            'user_id' => $this->userId,
            'export_type' => $this->exportType,
            'export_data' => $this->exportData,
        ]);
    }

    protected function buildMessage(): ReportMessage
    {
        return new OrderListMessage(
            modelId: $this->userId,
            exportType: $this->exportType,
            exportData: $this->exportData,
        );
    }
}
