<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\LocalMarketWebhook;
use App\Enums\LocalMarket\OrderStatus;
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
            case OrderStatus::CommoditiesPurchased:
                $this->data['auto_generate_financing_institution_certificate'] = 1;
                (new TraderStrategyContext($traderOrder->provider, $traderOrder->version))
                    ->updatePurchasingCommodity($traderOrder, $this->data);
                break;

            case OrderStatus::FailedPurchase:
                Trader::driver($traderOrder->provider, $traderOrder->version)
                    ->cancelTraderOrder($traderOrder, TraderOrderCancelReason::FailureToPurchase);
                Trader::driver($traderOrder->provider, $traderOrder->version)
                    ->confirmCancelledFromProvider($traderOrder);
                break;
            case OrderStatus::NoEligibleCommoditiesAvailable:
                Trader::driver($traderOrder->provider, $traderOrder->version)
                    ->cancelTraderOrder($traderOrder, TraderOrderCancelReason::NoEligibleCommoditiesAvailable);
                break;
            case OrderStatus::TransferOwnershipToCustomer:
                Trader::driver($traderOrder->provider, $traderOrder->version)
                    ->createSellingCommodityToCustomerDocument($traderOrder);
                break;
            case OrderStatus::CommoditiesSell:
                (new TraderStrategyContext($traderOrder->provider, $traderOrder->version))->updateMurabhaCompleteDocument($traderOrder);
                break;
            case OrderStatus::Cancelled:
                Trader::driver($traderOrder->provider, $traderOrder->version)
                    ->confirmCancelledFromProvider($traderOrder);
                break;
            case OrderStatus::FailedSell:
                Trader::driver($traderOrder->provider, $traderOrder->version)
                    ->cancelTraderOrder($traderOrder, TraderOrderCancelReason::FailureToSellAtLocalMarket);
                Trader::driver($traderOrder->provider, $traderOrder->version)
                    ->confirmCancelledFromProvider($traderOrder);
                break;

            default:
                throw new LocalMarketWebhookException;
        }
    }
}
