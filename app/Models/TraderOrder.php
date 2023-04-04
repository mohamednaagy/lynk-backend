<?php

namespace App\Models;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\MurabhaStep;
use App\Enums\TraderOrderStatus;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
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
 * @property TraderOrderStatus $status
 * @property Collection $traderHistories
 * @property Carbon $created_at
 */
class TraderOrder extends Model implements HasMedia
{
    use HasFactory, VirtualColumn, InteractsWithMedia;

    protected $fillable = [];

    protected $guarded = [];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'financing_order_id',
            'provider',
            'status',
            'reference',
            'updated_at',
            'created_at',
        ];
    }

    protected $casts = [
        'status' => TraderOrderStatus::class,
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
        $this
            ->addMediaCollection(TraderOrderMediaCollection::WarrantAmendmentExceptWarrantNo)
            ->singleFile();
        $this
            ->addMediaCollection(TraderOrderMediaCollection::TtiHoldingCertificate)
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

    public function isCancellable(): bool
    {
        if ($this->status->isNot(TraderOrderStatus::InProgress)) {
            return false;
        }

        $traderHistoryActions = $this->traderHistories->pluck('action')->toArray();

        return ! count(array_intersect(FinancingOrderHistory::$notCancellableActions, $traderHistoryActions));
    }

    public function checkOrderStepComplete(string $step): bool
    {
        if (! array_key_exists($step, MurabhaStep::$stepToHistoriesDictionary)) {
            throw new UnexpectedValueException('No mapping for this status');
        }

        return (bool) $this->traderHistories()
            ->where('action', end(MurabhaStep::$stepToHistoriesDictionary[$step]))
            ->first();
    }

    public function doesLastActionMatchWith($action): bool
    {
        if (! in_array($action, FinancingOrderHistory::getValues())) {
            throw new UnexpectedValueException('invalid Action');
        }

        $lastAction = $this->traderHistories()->latest('id')->first();

        return $lastAction->action == $action;
    }

    public function checkOrderHistoryAction($action): bool
    {
        if (! in_array($action, FinancingOrderHistory::getValues())) {
            throw new UnexpectedValueException('invalid Action');
        }

        return (bool) $this->traderHistories
            ->where('action', $action)
            ->first();
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

    protected function step(): Attribute
    {
        $lastAction = $this->traderHistories()->latest('id')->first();

        $stepNode = app(StepHistoriesDictionary::class)->getStepByHistory($lastAction?->action);

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
}
