<?php

namespace App\Models;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\MurabhaStep;
use App\Enums\TraderOrderStatus;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Stancl\VirtualColumn\VirtualColumn;
use UnexpectedValueException;

/**
 * @property mixed $reference
 * @property mixed $order
 * @property TraderOrderStatus $status
 * @property mixed $traderHistories
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

    public function checkOrderStepComplete(int $step): bool
    {
        if (! array_key_exists($step, MurabhaStep::$stepToHistoriesDictionary)) {
            throw new UnexpectedValueException('No mapping for this status');
        }

        return (bool) $this->traderHistories
            ->where('action', end(MurabhaStep::$stepToHistoriesDictionary[$step]))
            ->first();
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

    public function scopeLastHistory($query)
    {
        $query->addSelect(['last_history' => TraderHistory::select('action')
            ->whereColumn('trader_order_id', 'trader_orders.id')
            ->latest()
            ->take(1),
        ]);
    }

    /**
     * @throws OrderStatusDoesNotFollowSequenceException
     */
    public function ensureCanAccessStep(int $step)
    {
        if (! $this->checkOrderStepComplete($step)) {
            throw new OrderStatusDoesNotFollowSequenceException();
        }
    }

    public function canChangeParentOrderStatusIfStepWillBeUpdated(int $step): bool
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
