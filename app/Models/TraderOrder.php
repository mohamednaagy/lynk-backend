<?php

namespace App\Models;

use App\Enums\Area;
use App\Enums\ContractSignedType;
use App\Enums\CustomerDeliveryStatus;
use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\MurabhaStep;
use App\Enums\Trader as EnumsTrader;
use App\Enums\TraderOrderMode;
use App\Enums\TraderOrderStatus;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use App\Support\Traders\Facades\Trader;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Stancl\VirtualColumn\VirtualColumn;
use UnexpectedValueException;

/**
 * @property mixed $reference
 * @property mixed $order
 * @property mixed $provider
 * @property mixed $version
 * @property TraderOrderStatus $status
 * @property Collection $traderHistories
 * @property Carbon $created_at
 */
class TraderOrder extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, VirtualColumn;

    protected $fillable = [];

    protected $guarded = [];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'financing_order_id',
            'mode',
            'data',
            'provider',
            'version',
            'status',
            'is_base',
            'reference',
            'can_continue_progress',
            'updated_at',
            'created_at',
            'default_contract_sign_time_limit',
            'contract_signed_type',
        ];
    }

    protected $casts = [
        'status' => TraderOrderStatus::class,
        'contract_signed_type' => ContractSignedType::class,
        'can_continue_progress' => 'boolean',
    ];

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection(TraderOrderMediaCollection::ClientWakala)
            ->singleFile();
        $this
            ->addMediaCollection(TraderOrderMediaCollection::LenderWakala)
            ->singleFile();
        $this
            ->addMediaCollection(TraderOrderMediaCollection::PromiseToPurchase)
            ->singleFile();
        $this
            ->addMediaCollection(TraderOrderMediaCollection::MurabahaPurchaseOrder)
            ->singleFile();
        $this
            ->addMediaCollection(TraderOrderMediaCollection::TransferOwnershipToLender)
            ->singleFile();
        $this
            ->addMediaCollection(TraderOrderMediaCollection::SellingCommodityToCustomer)
            ->singleFile();
        $this->addMediaCollection(TraderOrderMediaCollection::BursamSellingCommodityToCustomer)
            ->singleFile();
        $this
            ->addMediaCollection(TraderOrderMediaCollection::WarrantAmendmentExceptWarrantNo)
            ->singleFile();
        $this
            ->addMediaCollection(TraderOrderMediaCollection::TtiHoldingCertificate)
            ->singleFile();

        $this->addMediaCollection(TraderOrderMediaCollection::BursamTtiHoldingCertificate)
            ->singleFile();

        $this->addMediaCollection(TraderOrderMediaCollection::ZatcaInvoice)
            ->singleFile();
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(FinancingOrder::class, 'financing_order_id', 'id');
    }

    public function traderHistories(): HasMany
    {
        return $this->hasMany(TraderHistory::class, 'trader_order_id', 'id');
    }

    public function isCancellable(?string $area): bool
    {
        return Trader::driver($this->provider, $this->version)
            ->isTraderOrderCancellable($this, $area);
    }

    public function checkOrderStepComplete(string $step): bool
    {
        $stepToHistoriesDictionary = trader_step_histories($this->provider, $this->version);

        if (! array_key_exists($step, $stepToHistoriesDictionary)) {
            throw new UnexpectedValueException("No mapping for this step {$step}");
        }

        return (bool) $this->traderHistories()
            ->where('action', end($stepToHistoriesDictionary[$step]))
            ->first();
    }

    public function doesLastActionMatchWith($actions): bool
    {
        if (! is_array($actions)) {
            $actions = [$actions];
        }

        foreach ($actions as $action) {
            if (! in_array($action, FinancingOrderHistory::getValues())) {
                throw new UnexpectedValueException('invalid Action');
            }
        }

        $lastAction = $this->traderHistories()->latest('id')->first();

        return in_array($lastAction->action, $actions);
    }

    public function checkOrderHistoryAction($actions): bool
    {
        return (bool) $this->getOrderHistoryAction($actions)->first();
    }

    public function getOrderHistoryAction($actions) 
    {
        if (! is_array($actions)) {
            $actions = [$actions];
        }

        foreach ($actions as $action) {
            if (! in_array($action, FinancingOrderHistory::getValues())) {
                throw new UnexpectedValueException(sprintf('Invalid action %s', $action));
            }
        }
        
        return $this->traderHistories()
            ->whereIn('action', $actions)
            ->get();
    }

    public function scopeWithLastHistoryAction($query)
    {
        return $query->addSelect([
            'last_history_action' => TraderHistory::select('action')
                ->whereColumn('trader_order_id', 'trader_orders.id')
                ->latest('id')
                ->take(1),
        ]);
    }

    protected function currentStep(): Attribute
    {
        $lastAction = $this->traderHistories()->latest('id')->first();

        $stepNode = (new StepHistoriesDictionary($this->provider, $this->version))->getStepByHistory($lastAction?->action);

        return new Attribute(
            get: fn () => $stepNode?->step,
        );
    }

    /**
     * @throws OrderStatusDoesNotFollowSequenceException
     */
    public function ensureCanAccessStep(string $step)
    {
        if (! $this->checkOrderStepComplete($step)) {
            throw new OrderStatusDoesNotFollowSequenceException;
        }
    }

    public function canChangeParentOrderStatusIfStepWillBeUpdated(string $step): bool
    {
        if ($this->status->isNot(TraderOrderStatus::InProgress)) {
            return false;
        }

        return ! $this->checkOrderStepComplete($step);
    }

    public function scopeCompletedOrInProgress($query)
    {
        return $query->whereIn('trader_orders.status', [TraderOrderStatus::Completed, TraderOrderStatus::InProgress]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', TraderOrderStatus::Completed);
    }

    public function isCommodityPurchased(): bool
    {
        return $this->checkOrderStepComplete(MurabhaStep::PurchasingCommodity);
    }

    public function isNeedToGenerateWakalaDocument()
    {
        if ($this->provider == EnumsTrader::Lynk && $this->mode = TraderOrderMode::Manual) {
            return false;
        }

        return ! $this->hasMedia(TraderOrderMediaCollection::ClientWakala);
    }

    public function isCancelled(): bool
    {
        return $this->status->is(TraderOrderStatus::Cancelled);
    }

    public function canBeCancelled(): bool
    {
        return $this->status->is(TraderOrderStatus::InProgress) || $this->status->is(TraderOrderStatus::Initiated);
    }

    public function getCancelStep(): ?string
    {
        return (new StepHistoriesDictionary($this->provider, $this->version))->getCancelStep($this)->step;
    }

    public function cancelDetail()
    {
        return $this->hasOne(TraderOrderCancelDetail::class, 'trader_order_id');
    }

    public function isDeliverable(): bool
    {
        return $this->provider == EnumsTrader::Lynk && $this->mode == TraderOrderMode::Manual;
    }

    public function getCustomerDeliveryStatusAndMessage(): array
    {
        if ($this->checkOrderHistoryAction(FinancingOrderHistory::DeliveryCancelled))
        {
            return [
                'status' => CustomerDeliveryStatus::DeliveryIgnoreAndSell,
                'message' => __('order.trader.lynk.steps.customer_delivery_confirmation.IgnoreAndSell'),
            ];
        } elseif ($this->checkOrderHistoryAction(FinancingOrderHistory::DeliveryConfirmed)) {
            return [
                'status' => CustomerDeliveryStatus::DeliveryConfirmed,
                'message' => __('order.trader.lynk.steps.customer_delivery_confirmation.DeliveryConfirmed'),
            ];
        } else {
            return [
                'status' => CustomerDeliveryStatus::DeliveryPending,
                'message' => __('order.trader.lynk.steps.customer_delivery_confirmation.pending'),
            ];
        }
    }

    /**
     * Check if the delivery is confirmed for the given trader order in the Lender area.
     */
    public function isDeliveryConfirmed(): bool
    {
        return (bool) $this->checkOrderHistoryAction(FinancingOrderHistory::DeliveryConfirmed);
    }
}
