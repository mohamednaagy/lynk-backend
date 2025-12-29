<?php

namespace App\Transformers;

use App\Enums\FinancingOrderStatus;
use App\Enums\FinancingOrderTypeEnum;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Enums\MurabhaStep;
use App\Enums\TraderOrderStatus;
use App\Exceptions\TraderNotSupportedException;
use App\Models\FinancingOrder;
use App\Models\Lender;
use App\Models\User;
use App\Transformers\TraderHistoryTransformers\TraderHistoryTransformerFactory;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class FinancingOrderTransformer extends TransformerAbstract
{
    protected ?Lender $lender;

    protected $area = null;

    protected User $user;

    public function __construct(?Lender $lender = null)
    {
        $this->lender = $lender;
    }

    protected array $defaultIncludes = [];

    protected array $availableIncludes = [
        'id',
        'status',
        'company_id',
        'company_name',
        'contract_number',
        'reference_number',
        'customer_name',
        'national_id',
        'amount',
        'amount_formatted',
        'selling_price',
        'selling_price_formatted',
        'is_verification_required',
        'is_updatable',
        'is_cancellable',
        'is_approved',
        'status_reason',
        'creator',
        'approver',
        'assignable_admin',
        'phone_country_code',
        'phone_number',
        'phone_number_formatted',
        'created_at',
        'current_step',
        'history',
        'active_trader',
        'trader_orders',
        'charged_trader_orders_count',
        'trader_order_history',
        'can_be_completed',
        'payment_proof_url',
        'can_create_trader_order',
        'company',
        'commodity_type',
        'commodity_type_id',
        'type',
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
        return $this->primitive($financingOrder->phone_number?->formatInternational());
    }

    public function includeId(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->id);
    }

    public function includeStatus(FinancingOrder $financingOrder)
    {
        $latest = $financingOrder->latestStatusHistory;

        return $this->primitive([
            'description' => $financingOrder->status->description,
            'value' => $financingOrder->status->value,
            'creator' => $latest?->getCreator(),
            'created_at' => $latest?->created_at ? saudi_now('Y-m-d h:i:s A', $latest->created_at) : null,
        ]);
    }

    public function includeCompanyId(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->company_id);
    }

    public function includeCompanyName(FinancingOrder $financingOrder): Primitive
    {
        if ($this->lender) {
            return $this->primitive($this->lender->name);
        }

        return $this->primitive($financingOrder->lender()->withTrashed()->first()->name);
    }

    public function includeReferenceNumber(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->reference_number);
    }

    public function includeCustomerName(FinancingOrder $financingOrder)
    {
        $customerName = $financingOrder->type->is(FinancingOrderTypeEnum::NormalLending) ? $financingOrder->getBorrowerInfo()['name'] : $financingOrder->getLenderInfo()['name'];

        return $this->primitive($customerName);
    }

    public function includeNationalId(FinancingOrder $financingOrder)
    {
        return $this->primitive((string) $financingOrder->national_id);
    }

    public function includeContractNumber(FinancingOrder $financingOrder)
    {
        return $this->primitive((string) $financingOrder->contract_number);
    }

    public function includePhoneNumber(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->mobile_dialing_phone_number);
    }

    public function includeAmount(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->amount->convertAndFormatByDecimal());
    }

    public function includeAmountFormatted(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->amount->convertAndFormatByDecimal(separator: ','));
    }

    public function includeSellingPrice(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->selling_price->convertAndFormatByDecimal());
    }

    public function includeSellingPriceFormatted(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->selling_price->convertAndFormatByDecimal(separator: ','));
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
        return $this->primitive(
            $financingOrder->status->is(FinancingOrderStatus::PendingApproval)
                || $financingOrder->status->is(FinancingOrderStatus::Rejected)
        );
    }

    public function includeIsCancellable(FinancingOrder $financingOrder): Primitive
    {
        return $this->primitive($financingOrder->isCancellable($this->area));
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

    public function includeCommodityTypeId(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->commodityType?->unique_name);
    }

    public function includeCreatedAt(FinancingOrder $financingOrder)
    {
        return $this->primitive(
            $financingOrder->created_at->clone()->tz('Asia/Riyadh')->format('Y-m-d h:i A')
        );
    }

    public function includeCurrentStep(FinancingOrder $financingOrder)
    {
        $step = $financingOrder->current_step;

        return $this->primitive($step ? [
            'value' => $step->value,
            'description' => $step->description,
        ] : null);
    }

    /**
     * @throws TraderNotSupportedException
     */
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

        $traderMurabhaSteps = collect(get_murabha_steps($activeTraderOrder->provider, $activeTraderOrder->version))
            ->except([
                MurabhaStep::TraderOrderCreated,
                MurabhaStep::TransferOwnershipToLender,
            ])
            ->keys()
            ->flatten()
            ->toArray();

        $historiesActions = $activeTraderOrder->traderHistories()->pluck('action')->toArray();

        return $this->collection([$historiesActions], TraderHistoryTransformerFactory::make($activeTraderOrder, $traderMurabhaSteps));
    }

    public function includeTraderOrders(FinancingOrder $financingOrder): Collection
    {
        return $this->collection(
            $financingOrder->traderOrders,
            (new TraderOrderTransformer)->setArea($this->area)->setCurrentOrderTraderOrders($financingOrder->traderOrders)
        );
    }

    public function includeActiveTrader(FinancingOrder $financingOrder): Primitive|Item
    {
        if (blank($financingOrder->activeTraderOrder->first())) {
            return $this->primitive(null);
        }

        return $this->item(
            $financingOrder->activeTraderOrder->first(),
            (new TraderOrderTransformer)->setArea($this->area)
        );
    }

    public function includeChargedTraderOrdersCount(FinancingOrder $financingOrder): Primitive
    {
        return $this->primitive((int) $financingOrder->charged_trader_orders_count);
    }

    public function includeTraderOrderHistory(FinancingOrder $financingOrder): Primitive
    {
        return $this->primitive(optional($financingOrder->activeTraderOrder->first())->traderHistories);
    }

    public function includeCanBeCompleted(FinancingOrder $financingOrder): Primitive
    {
        return $this->primitive($financingOrder->canBeCompleted($this->area));
    }

    public function includePaymentProofUrl(FinancingOrder $financingOrder): Primitive
    {
        $url = $financingOrder->getFirstMedia(FinancingOrderMediaCollection::PaymentProofFromLenderToCustomer);

        return $this->primitive($url?->file_url);
    }

    public function includeCanCreateTraderOrder(FinancingOrder $financingOrder): Primitive
    {
        return $this->primitive($financingOrder->canCreateTraderOrder($this->user));
    }

    public function setArea($area)
    {
        $this->area = $area;

        return $this;
    }

    public function setCurrentUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    public function includeAssignableAdmin(FinancingOrder $financingOrder)
    {
        if (is_null($financingOrder->assignable_id)) {
            return $this->primitive(null);
        }

        return $this->primitive([
            'id' => $financingOrder->responsableAdmin->id,
            'name' => $financingOrder->responsableAdmin->full_name,
        ]);
    }

    public function includeCompany(FinancingOrder $financingOrder)
    {
        $lender = $this->lender ?? $financingOrder->lender;

        return $this->primitive([
            'name' => $lender->name,
            'allow_preferred_commodity_in_order' => $lender->lenderDetail->allow_preferred_commodity_in_order,
        ]);
    }

    public function includeCommodityType(FinancingOrder $financingOrder)
    {
        $commodityType = $financingOrder->commodityType;
        if (! $commodityType) {
            return $this->primitive(null);
        }

        return $this->primitive($commodityType->only(['id', 'name']));
    }

    public function includeType(FinancingOrder $financingOrder)
    {
        return $this->primitive([
            'id' => $financingOrder->type,
            'name' => FinancingOrderTypeEnum::getDescription($financingOrder->type),
        ]);
    }
}
