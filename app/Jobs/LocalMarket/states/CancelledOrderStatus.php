<?php

namespace App\Jobs\LocalMarket\states;

use App\Actions\Contracts\Orders\LocalMarketWebhook;
use App\Enums\LocalMarketOrderStatus;
use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\InventoryService;
use App\Support\Traders\Traits\LocalMarketHelperTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CancelledOrderStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, LocalMarketHelperTrait, Queueable, SerializesModels;

    private LocalMarketWebhook $localMarketWebhook;

    private ?int $cancelReason;

    public function __construct(
        private LocalMarketOrder $localMarketOrder)
    {
        $this->localMarketWebhook = app(LocalMarketWebhook::class);

        $this->onQueue('local_market');
        $this->logQueueJob();
    }

    public function handle(): void
    {
        DB::beginTransaction();
        try {
            app(InventoryService::class)->freeOrderInventoryUnits($this->localMarketOrder);
            Log::channel('local_market')->info("refresh order inventories and units successfully {$this->localMarketOrder->id}");
            $this->localMarketWebhook->with(['case' => LocalMarketOrderStatus::Cancelled, 'external_order_no' => $this->localMarketOrder->external_order_no])->handle();
            $this->logQueueJob('Order cancelled successfully');
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('local_market')->error("failed to exec transaction cancel local market order id {$this->localMarketOrder->id}", ['message' => $e->getMessage()]);
            $this->localMarketOrder->changeStatusTo(LocalMarketOrderStatus::FailedToCancel);
        }

    }

    private function logQueueJob(?string $message = 'Cancel order status job added to queue local_market'): void
    {
        Log::channel('local_market')->info(
            "$message",
            ['order_id' => $this->localMarketOrder->id]
        );
    }
}
