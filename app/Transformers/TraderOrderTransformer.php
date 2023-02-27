<?php

namespace App\Transformers;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class TraderOrderTransformer extends TransformerAbstract
{
    protected array $defaultIncludes = [];

    protected array $availableIncludes = [
        'id',
        'financing_order_id',
        'reference',
        'provider',
        'purchasing_commodity_information',
        'status',
        'is_cancellable',
        'history',
        'created_at',
    ];

    public function transform(TraderOrder $traderOrder)
    {
        return [];
    }

    public function includeId(TraderOrder $traderOrder): Primitive
    {
        return $this->primitive($traderOrder->id);
    }

    public function includeFinancingOrderId(TraderOrder $traderOrder): Primitive
    {
        return $this->primitive($traderOrder->financing_order_id);
    }

    public function includeReference(TraderOrder $traderOrder): Primitive
    {
        return $this->primitive($traderOrder->reference);
    }

    public function includeProvider(TraderOrder $traderOrder): Primitive
    {
        return $this->primitive($traderOrder->provider);
    }

    public function includeIsCancellable(TraderOrder $traderOrder): Primitive
    {
        return $this->primitive($traderOrder->isCancellable());
    }

    public function includeHistory(TraderOrder $traderOrder): Collection
    {
        return $this->collection(collect([
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
            FinancingOrderHistory::ContractSigned,
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument,
            FinancingOrderHistory::ClientWakalaAccepted,
            FinancingOrderHistory::IssueMurabahaOffer,
            FinancingOrderHistory::MurabahaSaleCompleted,
        ]), new TraderHistoryTransformer($traderOrder));
    }

    public function includeStatus(TraderOrder $traderOrder): Primitive
    {
        return $this->primitive([
            'description' => TraderOrderStatus::fromValue($traderOrder->status)->description,
            'value' => TraderOrderStatus::fromValue($traderOrder->status)->value,
        ]);
    }

    public function includePurchasingCommodityInformation(TraderOrder $traderOrder): Primitive
    {
        return $this->primitive([
            'uom' => $traderOrder->uom,
            'owner' => $traderOrder->owner,
            'amount' => $traderOrder->amount,
            'product' => $traderOrder->product,
            'currency' => $traderOrder->currency,
            'quantity' => $traderOrder->quantity,
            'warehouse' => $traderOrder->warehouse,
            'ptp_document' => $traderOrder->getFirstMedia(TraderOrderMediaCollection::PromiseToPurchase)->file_url,
            'exchange_rate' => $traderOrder->exchange_rate,
            'previous_owner' => $traderOrder->previous_owner,
            'warehouse_or_vault_country' => $traderOrder->warehouse_or_vault_country,
            'warehouse_or_vault_emirates' => $traderOrder->warehouse_or_vault_emirates,
            'date_time_of_purchasing_commodity' => $traderOrder->date_time_of_purchasing_commodity,
            'original_holding_certificate' => $traderOrder->getFirstMedia(TraderOrderMediaCollection::TtiHoldingCertificate)->file_url,
            'auto_generate_financing_institution_certificate' => $traderOrder->auto_generate_financing_institution_certificate,
            'financing_institution_certificate' => $traderOrder->getFirstMedia(TraderOrderMediaCollection::TransferOwnershipToLender)->file_url,
        ]);
    }

    public function includeCreatedAt(TraderOrder $traderOrder): Primitive
    {
        return $this->primitive($traderOrder->created_at?->toDateTimeString());
    }
}
