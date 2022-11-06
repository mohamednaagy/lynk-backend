<?php

namespace App\Transformers;

use App\Enums\FinancingOrderHistory;
use App\Models\FinancingOrder;
use League\Fractal\Resource\Collection;
use League\Fractal\TransformerAbstract;

class FinancingOrderTransformer extends TransformerAbstract
{
    protected array $defaultIncludes = [
        'id',
        'status',
        'company_id',
        'reference_number',
        'national_id',
        'amount',
        'selling_price',
        'contract',
        'power_of_attorney',
        'is_approved',
        'status_reason',
        'history',
    ];

    protected array $availableIncludes = [
        'creator',
        'approver',
        'created_at',
    ];

    public function transform(FinancingOrder $financingOrder)
    {
        return [
            'phone_country_code' => $financingOrder->phone_number_country_code,
            'phone_number' => $financingOrder->mobile_dialing_phone_number,
            'phone_number_formatted' => $financingOrder->phone_number->formatInternational(),
        ];
    }

    public function includeId(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->id);
    }

    public function includeStatus(FinancingOrder $financingOrder)
    {
        return $this->primitive([
            'description' => $financingOrder->status->description,
            'value' => $financingOrder->status->value,
        ]);
    }

    public function includeCompanyId(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->company_id);
    }

    public function includeReferenceNumber(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->reference_number);
    }

    public function includeNationalId(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->national_id);
    }

    public function includePhoneNumber(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->phone_number);
    }

    public function includeAmount(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->amount);
    }

    public function includeSellingPrice(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->selling_price);
    }

    public function includeContract(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->contract);
    }

    public function includePowerOfAttorney(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->power_of_attorney);
    }

    public function includeCreatorName(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->creator->full_name);
    }

    public function includeCreator(FinancingOrder $financingOrder)
    {
        return $this->primitive([
            'id' => $financingOrder->creator->id,
            'name' => $financingOrder->creator->full_name,
        ]);
    }

    public function includeApprover(FinancingOrder $financingOrder)
    {
        return $this->primitive(optional($financingOrder->approver)->full_name);
    }

    public function includeIsApproved(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->approved_at !== null);
    }

    public function includeStatusReason(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->status_reason);
    }

    public function includeCreatedAt(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->created_at->format('Y-m-d h:mA'));
    }

    public function includeHistory(FinancingOrder $financingOrder): Collection
    {
        return $this->collection(collect([
            'client_wakala',
            FinancingOrderHistory::CommodityPurchased,
            FinancingOrderHistory::ContractSigned,
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument,
            FinancingOrderHistory::IssueMurabahaOffer,
            FinancingOrderHistory::MurabahaSaleCompleted,
        ]), new TraderHistoryTransformer($financingOrder, $financingOrder->traderOrders->last()->traderHistories));
    }
}
