<?php

namespace App\Models;

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
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamInitiatedTraderOrder;
use App\Support\Traders\Drivers\Lynk\Jobs\ProcessLynkInitiatedTraderOrder;
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

    protected $fillable = [
        'last_history_action',
        'last_history_action_updated_at',
    ];

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
            'contract_signed_type',
            'expire_at',
            'auto_sell_period_id',
            'commodity_type_id',

        ];
    }

    protected $casts = [
        'status' => TraderOrderStatus::class,
        'contract_signed_type' => ContractSignedType::class,
        'can_continue_progress' => 'boolean',
        'created_at' => 'datetime',
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

        $this
            ->addMediaCollection(TraderOrderMediaCollection::LynkSalePledgeCertificate)
            ->singleFile();

        $this
            ->addMediaCollection(TraderOrderMediaCollection::SellConfirmationDocument)
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
        $stepToHistoriesDictionary = trader_step_histories($this->provider, $this->version, $this->contract_signed_type);

        if (! array_key_exists($step, $stepToHistoriesDictionary)) {
            throw new UnexpectedValueException("No mapping for this step {$step}");
        }

        return $this->traderHistories()->where('action', end($stepToHistoriesDictionary[$step]))->exists();
    }

    /**
     * Update cached last history action for performance optimization
     */
    public function updateCachedLastHistoryAction(): void
    {
        $lastAction = $this->traderHistories()
            ->latest('id')
            ->value('action');

        $this->update([
            'last_history_action' => $lastAction,
            'last_history_action_updated_at' => now(),
        ]);
    }

    /**
     * Optimized version using cached values when available
     */
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

        // Use cached value if available and recent
        if ($this->last_history_action && $this->last_history_action_updated_at) {
            return in_array($this->last_history_action, $actions);
        }

        // Fallback to database query with optimized index
        $lastAction = $this->traderHistories()
            ->latest('id')
            ->value('action');

        if (! $lastAction) {
            return false;
        }

        // Update cache for future use
        $this->updateCachedLastHistoryAction();

        return in_array($lastAction, $actions);
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

    protected function currentStep(): Attribute
    {
        $lastAction = $this->traderHistories()->latest('id')->first();

        $stepNode = (new StepHistoriesDictionary($this->provider, $this->version, $this->contract_signed_type))->getStepByHistory($lastAction?->action);

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

    /**
     * Check if the trader order needs to be automatically processed.
     *
     *
     * @return bool Returns true if the trader order needs to be processed, false otherwise.
     */
    public function needsProcessingAfterInitiation()
    {
        if ($this->provider == EnumsTrader::Bursam && ($this->version !== 'v2' || $this->status->is(TraderOrderStatus::Hold))) {
            return false;
        }

        return $this->isAutomaticMode();
    }

    /**
     * Process the initiated trader order.
     *
     *
     * @return void
     */
    public function processInitiatedTraderOrder()
    {
        if ($this->provider == EnumsTrader::Bursam) {
            ProcessBursamInitiatedTraderOrder::dispatch($this->id);
        } elseif ($this->provider == EnumsTrader::Lynk) {
            ProcessLynkInitiatedTraderOrder::dispatch($this->id);
        }
    }

    public function company()
    {
        return $this->order->company;
    }

    public function isCancelled(): bool
    {
        return $this->status->is(TraderOrderStatus::Cancelled);
    }

    public function canBeCancelled(): bool
    {
        if ($this->isTraderManualAndPurchaseStepNotComplete()) {
            return true;
        }

        return ! $this->doesLastActionMatchWith([
            FinancingOrderHistory::GetTtiId, FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument,
        ]) && ($this->status->is(TraderOrderStatus::InProgress) || $this->status->is(TraderOrderStatus::Initiated) || $this->status->is(TraderOrderStatus::Hold));
    }

    public function isTraderManualAndPurchaseStepNotComplete(): bool
    {
        return ($this->status->is(TraderOrderStatus::InProgress) || $this->status->is(TraderOrderStatus::Initiated))
            && $this->mode === TraderOrderMode::Manual
            && $this->doesLastActionMatchWith(FinancingOrderHistory::GetTtiId);
    }

    public function getCancelStep(): ?string
    {
        return (new StepHistoriesDictionary($this->provider, $this->version, $this->contract_signed_type))->getCancelStep($this)?->step;
    }

    public function cancelDetail()
    {
        return $this->hasOne(TraderOrderCancelDetail::class, 'trader_order_id');
    }

    public function timeLimits()
    {
        return $this->hasMany(TraderOrderTimeLimit::class, 'trader_order_id', 'id');
    }

    public function hoverMessage(): ?string
    {
        return Trader::driver($this->provider, $this->version)->hoverMessageOfTraderStatus($this);
    }

    public function isDeliverable(): bool
    {
        return $this->provider == EnumsTrader::Lynk && $this->status->is(TraderOrderStatus::InProgress);
    }

    public function getCustomerDeliveryStatusAndMessage(): array
    {
        if ($this->checkOrderHistoryAction(FinancingOrderHistory::DeliveryCancelled)) {
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

    public function scopeCompletedWithContractSignedType($query, $contractSignedType = ContractSignedType::Sell)
    {
        return $query->completed()->where('contract_signed_type', $contractSignedType);
    }

    public function isDeliveryExpirable(): bool
    {
        return $this->doesLastActionMatchWith([FinancingOrderHistory::PendingDelivery]);
    }

    public function getRecentTimeLimit($type, $status)
    {
        return $this->timeLimits()
            ->where('type', $type)
            ->where('status', $status)
            ->orderBy('id', 'desc')
            ->first();
    }

    public function scopeForProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope for filtering by version.
     */
    public function scopeForVersion($query, string $version)
    {
        return $query->where('version', $version);
    }

    /**
     * Scope for filtering by hold status.
     */
    public function scopeHoldStatus($query)
    {
        return $query->where('status', TraderOrderStatus::Hold);
    }

    /**
     * Scope for filtering by automatic mode.
     */
    public function scopeMode($query, string $mode)
    {
        return $query->where('mode', $mode);
    }

    public function scopeGetHoldTraderOrder($query, string $provider = EnumsTrader::Bursam, string $version = 'v2')
    {
        return $query
            ->forProvider($provider)
            ->forVersion($version)
            ->holdStatus()
            ->mode(TraderOrderMode::Automatic)
            ->orderBy('id', 'asc');
    }

    public function allowProgressToNextStep(bool $value = true): void
    {
        $this->update(['can_continue_progress' => $value]);
    }

    public function scopeCompletedSellStep($query)
    {
        return $query->completed()->whereHas('traderHistories', function ($query) {
            $query->where('action', FinancingOrderHistory::MurabahaSaleCompleted);
        });
    }

    public function hasAutoCompleteFinancingOrder()
    {
        return $this->completedSellStep()->exists() &&
        $this->order->company->isCompanyHasMurabahaAutoCompleteOrder();
    }

    public function setAutoCompletePeriodId(int $periodId): void
    {
        $this->update(['auto_sell_period_id' => $periodId]);
    }

    public function isAutomaticMode(): bool
    {
        return $this->mode === TraderOrderMode::Automatic;
    }

    public function isProvider(string $provider): bool
    {
        return $this->provider === $provider;
    }

    public function isVersion(string $version): bool
    {
        return $this->version === $version;
    }

    public function isPreviousStepNotCompleted(string $currentStep): bool
    {
        $dictionary = new StepHistoriesDictionary(
            $this->provider,
            $this->version,
            $this->contract_signed_type
        );

        $previousStep = $dictionary->getPreviousStepOf($currentStep)->step;

        return ! $this->checkOrderStepComplete($previousStep);
    }

    public function commodityType()
    {
        return $this->belongsTo(CommodityType::class, 'commodity_type_id');
    }

    /**
     * Check if the trader order has "any" commodity type selected (commodity_type_id = -1)
     */
    public function hasAnyCommodityType(): bool
    {
        return $this->commodity_type_id === -1;
    }

    /**
     * Check if the trader order has a specific commodity type selected
     */
    public function hasSpecificCommodityType(): bool
    {
        return $this->commodity_type_id > 0;
    }
}
