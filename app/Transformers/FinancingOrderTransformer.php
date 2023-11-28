<?php

namespace App\Transformers;

use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Enums\MurabhaStep;
use App\Enums\TraderOrderStatus;
use App\Exceptions\TraderNotSupportedException;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class FinancingOrderTransformer extends TransformerAbstract
{
    protected ?Company $company;

    protected $area = null;

    protected User $user;

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
        if ($this->company) {
            return $this->primitive($this->company->name);
        }

        return $this->primitive($financingOrder->company->name);
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
        return $this->primitive($financingOrder->amount->convertAndFormatByDecimal());
    }

    public function includeAmountFormatted(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->amount->convertAndFormatByDecimal(sperator: ','));
    }

    public function includeSellingPrice(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->selling_price->convertAndFormatByDecimal());
    }

    public function includeSellingPriceFormatted(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->selling_price->convertAndFormatByDecimal(sperator: ','));
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

        return $this->collection([$historiesActions], new TraderHistoryTransformer($activeTraderOrder, $traderMurabhaSteps));
    }

    public function includeTraderOrders(FinancingOrder $financingOrder): Collection
    {
        return $this->collection(
            $financingOrder->traderOrders,
            (new TraderOrderTransformer())->setArea($this->area)->setCurrentOrderTraderOrders($financingOrder->traderOrders)
        );
    }

    public function includeActiveTrader(FinancingOrder $financingOrder): Primitive|Item
    {
        if (blank($financingOrder->activeTraderOrder->first())) {
            return $this->primitive(null);
        }

        return $this->item(
            $financingOrder->activeTraderOrder->first(),
            (new TraderOrderTransformer())->setArea($this->area)
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
        return $this->primitive($financingOrder->canBeCompleted());
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
}
