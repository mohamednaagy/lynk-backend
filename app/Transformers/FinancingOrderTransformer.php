<?php

namespace App\Transformers;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class FinancingOrderTransformer extends TransformerAbstract
{
    protected array $defaultIncludes = [];

    protected array $availableIncludes = [
        'id',
        'status',
        'company_id',
        'reference_number',
        'national_id',
        'amount',
        'selling_price',
        'is_updatable',
        'is_approved',
        'status_reason',
        'creator',
        'approver',
        'phone_country_code',
        'phone_number',
        'phone_number_formatted',
        'created_at',
        'history',
        'active_trader',
        'trader_order_history',
    ];

    public function transform(FinancingOrder $financingOrder)
    {
        return [];
    }

    public function includePhoneCountryCode(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->phone_number_country_code);
    }

    public function includePhoneNumberFormatted(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->phone_number->formatInternational());
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
        return $this->primitive((string) $financingOrder->national_id);
    }

    public function includePhoneNumber(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->mobile_dialing_phone_number);
    }

    public function includeAmount(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->amount->formatByDecimal());
    }

    public function includeSellingPrice(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->selling_price->formatByDecimal());
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

    public function includeIsUpdatable(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->status->is(FinancingOrderStatus::PendingApproval));
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
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
            FinancingOrderHistory::ContractSigned,
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument,
            'client_wakala',
            FinancingOrderHistory::IssueMurabahaOffer,
            FinancingOrderHistory::MurabahaSaleCompleted,
        ]), new TraderHistoryTransformer($financingOrder, $financingOrder->traderOrders->last()->traderHistories ?? collect()));
    }

    public function includeActiveTrader(FinancingOrder $financingOrder): Primitive|Item
    {
        if (blank($financingOrder->activeTraderOrder->first())) {
            return $this->primitive(null);
        }

        return $this->item($financingOrder->activeTraderOrder->first(), new TraderOrderTransformer());
    }

    public function includeTraderOrderHistory(FinancingOrder $financingOrder): Primitive
    {
        return $this->primitive(optional($financingOrder->activeTraderOrder->first())->traderHistories);
    }
}
