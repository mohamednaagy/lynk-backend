<?php

namespace App\Models;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\MurabhaStep;
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
        ];
    }

    protected $casts = [
        'status' => TraderOrderStatus::class,
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
        if ($this->status->isNot(TraderOrderStatus::InProgress)) {
            return false;
        }

        return Trader::driver($this->provider, $this->version)
            ->isTraderOrderCancellable($this, $area);
    }

    public function checkOrderStepComplete(string $step): bool
    {
        $stepToHistoriesDictionary = trader_step_histories($this->provider, $this->version);

        if (! array_key_exists($step, $stepToHistoriesDictionary)) {
            throw new UnexpectedValueException('No mapping for this step');
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
            ->exists();
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
            throw new OrderStatusDoesNotFollowSequenceException();
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
}
