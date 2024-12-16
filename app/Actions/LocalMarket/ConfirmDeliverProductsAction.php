<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\ConfirmDeliverProducts;
use App\Enums\LocalMarketOrderStatus;
use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConfirmDeliverProductsAction implements ConfirmDeliverProducts
{
    private InventoryService $inventoryService;

    public function __construct(
    ) {
        $this->inventoryService = app(InventoryService::class);
    }

    public function handle(LocalMarketOrder $localMarketOrder): void
    {
        DB::beginTransaction();
        try {
            $this->inventoryService->confirmDeliverOrderUnits($localMarketOrder);
            $localMarketOrder->changeStatusTo(LocalMarketOrderStatus::Delivered);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('local_market')->error('Error in ConfirmDeliverProductsAction', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $localMarketOrder->changeStatusTo(LocalMarketOrderStatus::FailedDelivery);
        }
    }
}
