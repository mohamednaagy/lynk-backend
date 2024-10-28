<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\LocalMarketWebhook;
use App\Enums\LocalMarketOrderStatus;
use App\Enums\TraderOrderCancelReason;
use App\Exceptions\LocalMarketWebhookException;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\TradingStrategies\TraderStrategyContext;
use Illuminate\Support\Facades\Log;

class LocalMarketWebhookAction implements LocalMarketWebhook
{
    private array $data = [];

    /**
     * @return $this
     */
    public function with(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function handle(): void
    {
        $traderOrder = TraderOrder::lockForUpdate()
            ->where('reference', $this->data['external_order_no'])
            ->firstOrFail();

        Log::channel('local_market')->info(
            "Update trader by local market webhook. Trader Order ID: {$traderOrder->id} with case => ".$this->data['case']
        );

        switch ($this->data['case']) {
            case LocalMarketOrderStatus::CommoditiesPurchased:
                $this->data['auto_generate_financing_institution_certificate'] = 1;
                (new TraderStrategyContext($traderOrder->provider, $traderOrder->version))
                    ->updatePurchasingCommodity($traderOrder, $this->data);
                break;

            case LocalMarketOrderStatus::FailedPurchase:
                Trader::driver($traderOrder->provider, $traderOrder->version)
                    ->cancelTraderOrder($traderOrder, TraderOrderCancelReason::FailureToPurchase);
                break;

            case LocalMarketOrderStatus::NoEligibleCommoditiesAvailable:
                Trader::driver($traderOrder->provider, $traderOrder->version)
                    ->cancelTraderOrder($traderOrder, TraderOrderCancelReason::NoEligibleCommoditiesAvailable);
                break;
            case LocalMarketOrderStatus::TransferOwnershipToCustomer:
                Trader::driver($traderOrder->provider, $traderOrder->version)
                    ->createSellingCommodityToCustomerDocument($traderOrder);
                break;
            case LocalMarketOrderStatus::CommoditiesSell:
                (new TraderStrategyContext($traderOrder->provider, $traderOrder->version))->updateMurabhaCompleteDocument($traderOrder);
                break;
            case LocalMarketOrderStatus::Cancelled:

                break;
            case LocalMarketOrderStatus::FailedSell:
                Trader::driver($traderOrder->provider, $traderOrder->version)
                    ->cancelTraderOrder($traderOrder, TraderOrderCancelReason::FailureToSellAtLocalMarket);
                break;
            default:
                throw new LocalMarketWebhookException;
        }
    }
}
