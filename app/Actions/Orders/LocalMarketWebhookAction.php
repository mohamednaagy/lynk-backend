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
        $traderOrder = TraderOrder::where('reference', $this->data['external_order_no'])->first();

        if (! $traderOrder) {
            Log::channel(LOG_CHANNEL_LOCAL_MARKET)->error("Trader order not found for the given reference => {$this->data['external_order_no']}", [
                'reference' => $this->data['external_order_no'],
            ]);
            throw new LocalMarketWebhookException;
        }

        Log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(formatLogTitle('Update trader by local market webhook with case => '.$this->data['case'], $traderOrder), [
            'financingOrderId' => $traderOrder->financing_order_id,
            'traderOrderId' => $traderOrder->id,
            'reference' => $this->data['external_order_no'],
            'case' => $this->data['case'],
        ]);

        switch ($this->data['case']) {
            case OrderStatus::CommoditiesPurchased:
                $this->data['auto_generate_financing_institution_certificate'] = 1;
                (new TraderStrategyContext($traderOrder->provider, $traderOrder->version))
                    ->updatePurchasingCommodity($traderOrder, $this->data);

                $traderOrder->allowProgressToNextStep(false); // TODO: Added to explicitly control order transitions (needs refactoring later)

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
            case 'commodities_settled':
                Trader::driver($traderOrder->provider, $traderOrder->version)->createSellConfirmationDocument($traderOrder);
                break;
            default:
                throw new LocalMarketWebhookException;
        }
    }
}
