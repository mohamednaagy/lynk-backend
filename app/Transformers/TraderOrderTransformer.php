<?php

namespace App\Transformers;

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
            'hsCode' => $traderOrder->hsCode,
            'product' => $traderOrder->product,
            'currency' => $traderOrder->currency,
            'newOwner' => $traderOrder->newOwner,
            'quantity' => $traderOrder->quantity,
            'warehouse' => $traderOrder->warehouse,
            'warrantNo' => $traderOrder->warrantNo,
            'created_at' => $traderOrder->created_at,
            'updated_at' => $traderOrder->updated_at,
            'ptpDocument' => $traderOrder->ptpDocument,
            'exchangeRate' => $traderOrder->exchangeRate,
            'previousOwner' => $traderOrder->previousOwner,
            'inventoryRecordId' => $traderOrder->inventoryRecordId,
            'warrantPercentage' => $traderOrder->warrantPercentage,
            'warehouseOrVaultCountry' => $traderOrder->warehouseOrVaultCountry,
            'warehouseOrVaultEmirates' => $traderOrder->warehouseOrVaultEmirates,
            'warehouseOrVaultOperatorId' => $traderOrder->warehouseOrVaultOperatorId,
            'dateTimeOfPurchasingCommodity' => $traderOrder->dateTimeOfPurchasingCommodity,
            'originalHoldingCertificate' => $traderOrder->originalHoldingCertificate,
            'autoGenerateFinancingInstitutionCertificate' => $traderOrder->autoGenerateFinancingInstitutionCertificate,
            'financingInstitutionCertificate' => $traderOrder->financingInstitutionCertificate,
        ]);
    }
}
