<?php

namespace App\Jobs\LocalMarket\states;

use App\Actions\Contracts\Orders\LocalMarketWebhook;
use App\Enums\LocalMarket\OwnershipTypes;
use App\Enums\LocalMarketOrderStatus;
use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\InventoryService;
use App\Services\LocalMarket\OwnershipService;
use App\Support\Traders\Traits\LocalMarketHelperTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PendingSellOrderStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, LocalMarketHelperTrait, Queueable, SerializesModels;

    private InventoryService $inventoryService;

    private OwnershipService $ownershipService;

    private LocalMarketWebhook $localMarketWebhook;

    public function __construct(
        private LocalMarketOrder $localMarketOrder
    ) {
        $this->inventoryService = app(InventoryService::class);
        $this->ownershipService = app(OwnershipService::class);
        $this->localMarketWebhook = app(LocalMarketWebhook::class);

        $this->onQueue('local_market');
        $this->logQueueJob();
    }

    public function handle(): void
    {
        DB::beginTransaction();
        try {
            $this->ownershipService->changeUnitOwnership($this->localMarketOrder, OwnershipTypes::TraderOrder, $this->localMarketOrder->external_order_no);
            $this->inventoryService->freeOrderInventoryUnits($this->localMarketOrder);
            DB::commit();
            $this->localMarketOrder->changeStatusTo(LocalMarketOrderStatus::CommoditiesSell);
            $this->logQueueJob('pending successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->localMarketOrder->changeStatusTo(LocalMarketOrderStatus::FailedSell);

        }
    }

    private function logQueueJob(?string $message = 'Pending Sell order status job added to queue local_market'): void
    {
        Log::channel('local_market')->info(
            "$message",
            ['order_id' => $this->localMarketOrder->id]
        );
    }
}
