<?php

namespace App\Jobs\LocalMarket\states;

use App\Actions\Contracts\Orders\LocalMarketWebhook;
use App\Enums\ContractSignedType;
use App\Enums\LocalMarket\OwnershipTypes;
use App\Enums\LocalMarket\UnitOwnershipAction;
use App\Enums\LocalMarketOrderStatus;
use App\Models\LocalMarketOrder;
use App\Models\TraderOrder;
use App\Services\LocalMarket\OwnershipService;
use App\Services\LocalMarket\UnitService;
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

    private UnitService $unitService;

    public function __construct(
        private LocalMarketOrder $localMarketOrder
    ) {

        $this->localMarketWebhook = app(LocalMarketWebhook::class);
        $this->ownershipService = app(OwnershipService::class);
        $this->unitService = app(UnitService::class);

        $this->onQueue('local_market');
        $this->logQueueJob();
    }

    public function handle(): void
    {

        $this->unitService->changeOrderUnitsOwnershipTo($this->localMarketOrder, OwnershipTypes::Customer, $this->localMarketOrder->customer_name, UnitOwnershipAction::BorrowerOwnershipTransfer);
        $this->localMarketWebhook->with(['case' => LocalMarketOrderStatus::TransferOwnershipToCustomer, 'external_order_no' => $this->localMarketOrder->external_order_no])->handle();
        $this->logQueueJob('Transfer Ownership to customer successfully');

        $traderOrder = TraderOrder::lockForUpdate()
            ->where('reference', $this->localMarketOrder->external_order_no)
            ->firstOrFail();
        Log::channel('local_market')->info("Trader Order ID: {$traderOrder->id} with status => ".$traderOrder->contract_signed_type);
        match ($traderOrder->contract_signed_type) {
            ContractSignedType::Sell => $this->localMarketOrder->changeStatusTo(LocalMarketOrderStatus::PendingSellCommodities),
            ContractSignedType::Delivery => $this->localMarketOrder->changeStatusTo(LocalMarketOrderStatus::PendingDelivery),
        };
        Log::channel('local_market')->info("Local Market Order ID: {$this->localMarketOrder->id} with status => ".$this->localMarketOrder->status);
    }

    private function logQueueJob(?string $message = 'Transfer Ownership to customer status job added to queue local_market'): void
    {
        Log::channel('local_market')->info(
            "$message",
            ['order_id' => $this->localMarketOrder->id]
        );
    }
}
