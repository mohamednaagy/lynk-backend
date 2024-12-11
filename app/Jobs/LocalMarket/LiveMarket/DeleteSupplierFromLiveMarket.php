<?php

namespace App\Jobs\LocalMarket\LiveMarket;

use App\Models\Company;
use App\Services\LocalMarket\LiveMarketService;

class DeleteSupplierFromLiveMarket extends BaseLiveMarketJob
{
    protected Company $supplier;

    public function __construct(Company $supplier)
    {
        parent::__construct();
        $this->supplier = $supplier;
    }

    public function handle(LiveMarketService $liveMarketService): void
    {
        $this->logJobStart('Starting to delete supplier from live market', [
            'supplier_id' => $this->supplier->id,
            'supplier_name' => $this->supplier->name,
        ]);

        try {
            $liveMarketService->handleCompanyRemoval($this->supplier);

            $this->logJobSuccess('Successfully deleted supplier from live market', [
                'supplier_id' => $this->supplier->id,
                'supplier_name' => $this->supplier->name,
            ]);
        } catch (\Throwable $e) {
            $this->logJobError('Failed to delete supplier from live market', $e);
            throw $e;
        }
    }

    protected function getFailedJobContext(): array
    {
        return [
            'supplier_id' => $this->supplier->id,
            'supplier_name' => $this->supplier->name,
        ];
    }
}
