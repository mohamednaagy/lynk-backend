<?php

namespace App\Actions\Orders\Webhooks;

use App\Actions\Contracts\Orders\Webhooks\FireWebhookWhenStatusIsCommodityPurchased;
use App\Actions\Orders\Webhooks\Traits\OrderWebhooksHelper;
use App\Enums\DocumentType;
use App\Enums\MurabhaStep;
use App\Enums\Trader;
use App\Enums\TraderOrderTimeLimitStatus;
use App\Enums\TraderOrderTimeLimitType;
use App\Enums\WebhookType;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Webhooks\Facades\WebhookEvent;
use Carbon\Carbon;

class FireWebhookWhenStatusIsCommodityPurchasedAction implements FireWebhookWhenStatusIsCommodityPurchased
{
    use OrderWebhooksHelper;

    public function handle(FinancingOrder $financingOrder, TraderOrder $traderOrder): void
    {
        if (is_null($traderOrder->products)) {
            return;
        }
        $lastHistory = $this->getTraderOrderLastHistory($traderOrder);
        $lastCompletedStep = $this->getCompletedStep($traderOrder);
        $nextStep = $this->getDictionaryOfTraderOrder($traderOrder)
            ->getNextStepOf($lastCompletedStep);

        $effective_at = $traderOrder->getRecentTimeLimit(TraderOrderTimeLimitType::ContractSignTimeLimit, TraderOrderTimeLimitStatus::Pending)?->effective_at;
        $isBursaOrder = $traderOrder->provider === Trader::Bursam;
        $lender = $financingOrder->lender()->withTrashed()->first();
        WebhookEvent::fire($lender, WebhookType::OrderUpdates, [
            'order_id' => $financingOrder->id,
            'order_status' => [
                'value' => $financingOrder->status->value,
                'label' => $financingOrder->status->description,
            ],
            'trading_information' => [
                'trading_id' => $traderOrder->id,
                'trading_reference' => $traderOrder->reference,
                'current_trading_step' => $this->getUiStepName($nextStep?->step),
                'completed_murabaha_step' => $this->getUiStepName($lastCompletedStep),
                'products' => $this->resolveProducts($traderOrder),
                'cert_document_url' => $isBursaOrder ? formatMediaUrl(route('api.v1.admins.generate', [
                    'document_type' => DocumentType::BURSAM_BID_CERTIFICATE,
                    'context' => [
                        'trader_order_id' => $traderOrder->id,
                    ],
                ])) : null,
                'ownership_document_url' => formatMediaUrl(route('api.v1.admins.generate', [
                    'document_type' => DocumentType::TRANSFER_OWNERSHIP_TO_LENDER,
                    'context' => [
                        'trader_order_id' => $traderOrder->id,
                    ],
                ])),
                'expiry_date' => $effective_at ? saudi_now('Y-m-d h:i:s A', Carbon::parse($effective_at)) : null,
            ],
            'updated_at' => $this->getFormattedDateTime($lastHistory),
        ]);
    }

    protected function getCompletedStepOfTrader($provider): string
    {
        return MurabhaStep::PurchasingCommodity;
    }
}
