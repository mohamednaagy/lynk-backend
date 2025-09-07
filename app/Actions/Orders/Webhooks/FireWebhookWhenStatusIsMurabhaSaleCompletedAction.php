<?php

namespace App\Actions\Orders\Webhooks;

use App\Actions\Contracts\Orders\Webhooks\FireWebhookWhenStatusIsMurabhaSaleCompleted;
use App\Actions\Orders\Webhooks\Traits\OrderWebhooksHelper;
use App\Enums\DocumentType;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\MurabhaStep;
use App\Enums\Trader;
use App\Enums\WebhookType;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Webhooks\Facades\WebhookEvent;

class FireWebhookWhenStatusIsMurabhaSaleCompletedAction implements FireWebhookWhenStatusIsMurabhaSaleCompleted
{
    use OrderWebhooksHelper;

    public function handle(FinancingOrder $financingOrder, TraderOrder $traderOrder): void
    {
        $isBursam = $traderOrder->provider === TraderEnum::Bursam;
        $sellConfirmationMediaCollection = match ($traderOrder->provider) {
            Trader::Dmcc, Trader::FakeDmcc => null,
            Trader::Bursam => null,
            Trader::Lynk => TraderOrderMediaCollection::SellConfirmationDocument,
        };

        $wakalaDocumentMediaFile = get_media_of_model($traderOrder, TraderOrderMediaCollection::SignedClientWakala);
        $lastHistory = $this->getTraderOrderLastHistory($traderOrder);
        $lastCompletedStep = $this->getCompletedStep($traderOrder);
        $company = $financingOrder->company()->withTrashed()->first();
        WebhookEvent::fire($company, WebhookType::OrderUpdates, [
            'order_id' => $financingOrder->id,
            'order_status' => [
                'value' => $financingOrder->status->value,
                'label' => $financingOrder->status->description,
            ],
            'trading_information' => [
                'trading_id' => $traderOrder->id,
                'trading_reference' => $traderOrder->reference,
                'current_trading_step' => 'completed',
                'completed_murabaha_step' => $lastCompletedStep,
                'signed_wakala_document_url' => get_file_url($wakalaDocumentMediaFile),
                'warranty_document_url' => route('api.v1.admins.generate', [
                    'document_type' => $isBursam ? DocumentType::BURSAM_OTC_CERTIFICATE : DocumentType::SELLING_PLEDGE_CERTIFICATE,
                    'context' => [
                        'trader_order_id' => $traderOrder->id,
                    ],
                ]),
            ],
            'updated_at' => $this->getFormattedDateTime($lastHistory),
        ]);
    }

    protected function getCompletedStepOfTrader($provider)
    {
        return MurabhaStep::MurabahaSaleCompleted;
    }
}
