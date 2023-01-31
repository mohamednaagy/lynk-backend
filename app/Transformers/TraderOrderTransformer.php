<?php

namespace App\Transformers;

use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
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
            'hs_code' => $traderOrder->hsCode,
            'product' => $traderOrder->product,
            'currency' => $traderOrder->currency,
            'new_owner' => $traderOrder->newOwner,
            'quantity' => $traderOrder->quantity,
            'warehouse' => $traderOrder->warehouse,
            'warrant_no' => $traderOrder->warrantNo,
            'created_at' => $traderOrder->created_at,
            'updated_at' => $traderOrder->updated_at,
            'ptp_document' => $this->fileUrl($traderOrder->order->getMedia(FinancingOrderMediaCollection::PromiseToPurchase)->first()),
            'exchange_rate' => $traderOrder->exchangeRate,
            'previous_owner' => $traderOrder->previousOwner,
            'inventory_record_id' => $traderOrder->inventoryRecordId,
            'warrant_percentage' => $traderOrder->warrantPercentage,
            'warehouse_or_vault_country' => $traderOrder->warehouseOrVaultCountry,
            'warehouse_or_vault_emirates' => $traderOrder->warehouseOrVaultEmirates,
            'warehouse_or_vault_operator_id' => $traderOrder->warehouseOrVaultOperatorId,
            'date_time_of_purchasing_commodity' => $traderOrder->dateTimeOfPurchasingCommodity,
            'original_holding_certificate' => $this->fileUrl($traderOrder->order->getMedia(FinancingOrderMediaCollection::TtiHoldingCertificate)->first()),
            'auto_generate_financing_institution_certificate' => $traderOrder->autoGenerateFinancingInstitutionCertificate,
            'financing_institution_certificate' => $this->fileUrl($traderOrder->order->getMedia(FinancingOrderMediaCollection::TransferOwnershipToLender)->first()),
        ]);
    }

    private function fileUrl($media): ?string
    {
        if ($media) {
            return route('api.v1.media.download', ['media' => $media->uuid]);
        }

        return null;
    }
}
