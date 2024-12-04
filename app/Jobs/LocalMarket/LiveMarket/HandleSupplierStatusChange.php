<?php

namespace App\Jobs\LocalMarket\LiveMarket;

use App\Models\Company;
use App\Services\LocalMarket\LiveMarketService;

class HandleSupplierStatusChange extends BaseLiveMarketJob
{
    protected Company $supplier;

    public function __construct(Company $supplier)
    {
        parent::__construct();
        $this->supplier = $supplier;
    }

    public function handle(LiveMarketService $liveMarketService): void
    {
        $this->logJobStart('Processing supplier status change', [
            'supplier_id' => $this->supplier->id,
            'supplier_name' => $this->supplier->name,
            'current_status' => $this->supplier->detail?->status->value,
        ]);

        try {
            $liveMarketService->handleSupplierStatusChange($this->supplier);

            $this->logJobSuccess('Successfully processed supplier status change', [
                'supplier_id' => $this->supplier->id,
                'supplier_name' => $this->supplier->name,
            ]);
        } catch (\Throwable $e) {
            $this->logJobError('Failed to process supplier status change', $e);
            throw $e;
        }
    }

    protected function getFailedJobContext(): array
    {
        return [
            'supplier_id' => $this->supplier->id,
            'supplier_name' => $this->supplier->name,
            'current_status' => $this->supplier->detail?->status->value,
        ];
    }
}
