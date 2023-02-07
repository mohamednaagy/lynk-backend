<?php

namespace App\Models;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\TraderOrderStatus;
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

    public function checkOrderStepComplete(int $status): bool
    {
        if (! array_key_exists($status, FinancingOrderHistory::$orderHistoryLastActionMap)) {
            throw new UnexpectedValueException('no mapping for this status');
        }

        return (bool) $this->traderHistories
            ->where('action', FinancingOrderHistory::$orderHistoryLastActionMap[$status])
            ->first();
    }
}
