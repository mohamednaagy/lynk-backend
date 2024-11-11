<?php

namespace App\Jobs\LocalMarket\states;

use App\Actions\Contracts\Orders\LocalMarketWebhook;
use App\Enums\LocalMarket\OwnershipTypes;
use App\Enums\LocalMarketOrderStatus;
use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\OwnershipService;
use App\Support\Traders\Traits\LocalMarketHelperTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TransferCommodityToCustomerStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, LocalMarketHelperTrait, Queueable, SerializesModels;

    private LocalMarketWebhook $localMarketWebhook;

    private OwnershipService $ownershipService;

    public function __construct(
        private LocalMarketOrder $localMarketOrder
    ) {

        $this->localMarketWebhook = app(LocalMarketWebhook::class);
        $this->ownershipService = app(OwnershipService::class);

        $this->onQueue('local_market');
        $this->logQueueJob();
    }

    public function handle(): void
    {

        $this->ownershipService->changeUnitOwnership($this->localMarketOrder, OwnershipTypes::Customer, $this->localMarketOrder->customer_name);
        $this->localMarketWebhook->with(['case' => LocalMarketOrderStatus::TransferOwnershipToCustomer, 'external_order_no' => $this->localMarketOrder->external_order_no])->handle();
        $this->logQueueJob('Transfer Ownership to customer successfully');
        $this->localMarketOrder->changeStatusTo(LocalMarketOrderStatus::PendingSellCommodities);
    }

    private function logQueueJob(?string $message = 'Transfer Ownership to customer status job added to queue local_market'): void
    {
        Log::channel('local_market')->info(
            "$message",
            ['order_id' => $this->localMarketOrder->id]
        );
    }
}
