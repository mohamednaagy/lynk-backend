<?php

namespace App\Transformers;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\FinancingOrder;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class FinancingOrderTransformer extends TransformerAbstract
{
    protected ?Company $company;

    public function __construct(Company $company = null)
    {
        $this->company = $company;
    }

    protected array $defaultIncludes = [];

    protected array $availableIncludes = [
        'id',
        'status',
        'company_id',
        'company_name',
        'reference_number',
        'customer_name',
        'national_id',
        'amount',
        'selling_price',
        'is_verification_required',
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
        'trader_orders',
        'trader_order_history',
        'can_be_completed',
        'payment_proof_url',
        'can_create_trader_order',
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

    public function includeCompanyName(FinancingOrder $financingOrder): Primitive
    {
        return $this->primitive($this->company->name);
    }

    public function includeReferenceNumber(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->reference_number);
    }

    public function includeCustomerName(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->customer_name);
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

    public function includeCreator(FinancingOrder $financingOrder)
    {
        if (is_null($financingOrder->creator)) {
            return $this->primitive(null);
        }

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

    public function includeIsVerificationRequired(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->is_verification_required);
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

    public function includeHistory(FinancingOrder $financingOrder): Primitive|Collection
    {
        // TODO: handle not expired + cancelled cases or show all trading requests
        $activeTraderOrder = $financingOrder->traderOrders()
            ->whereIn('status', [
                TraderOrderStatus::Completed,
                TraderOrderStatus::InProgress,
            ])
            ->latest()
            ->first();

        if (! $activeTraderOrder) {
            return $this->primitive(null);
        }

        return $this->collection(collect([
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
            FinancingOrderHistory::ContractSigned,
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument,
            FinancingOrderHistory::ClientWakalaAccepted,
            FinancingOrderHistory::IssueMurabahaOffer,
            FinancingOrderHistory::MurabahaSaleCompleted,
        ]), new TraderHistoryTransformer($activeTraderOrder));
    }

    public function includeTraderOrders(FinancingOrder $financingOrder): Collection
    {
        return $this->collection($financingOrder->traderOrders, new TraderOrderTransformer());
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

    public function includeCanBeCompleted(FinancingOrder $financingOrder): Primitive
    {
        return $this->primitive($financingOrder->canBeCompleted());
    }

    public function includePaymentProofUrl(FinancingOrder $financingOrder): Primitive
    {
        $url = $financingOrder->getFirstMedia(FinancingOrderMediaCollection::PaymentProofFromLenderToCustomer);

        return $this->primitive($url?->file_url);
    }

    public function includeCanCreateTraderOrder(FinancingOrder $financingOrder): Primitive
    {
        return $this->primitive($financingOrder->CanCreateTraderOrder());
    }
}
