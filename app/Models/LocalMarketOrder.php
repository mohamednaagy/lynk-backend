<?php

namespace App\Models;

use App\Enums\LocalMarket\OrderStatus;
use App\Support\Traders\Traits\LocalMarketHelperTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * @property mixed $currency
 */
class LocalMarketOrder extends Model
{
    use HasFactory, LocalMarketHelperTrait;

    protected $fillable = [
        'source',
        'amount',
        'national_id',
        'status',
        'data',
        'comment',
        'company_id',
        'external_order_no',
        'buying_uuid',
        'selling_uuid',
        'currency',
        'hold_for',
        'order_no',
        'is_commodities_settled',
        'preferred_commodity_type',
        'lender_identifier',
        'borrower_identifier',
    ];

    protected $attributes = [
        'status' => OrderStatus::initiate,
    ];

    protected $casts = [
        'is_commodities_settled' => 'boolean',
        'preferred_commodity_type' => 'array',
        'data' => 'array',
    ];

    public function orderInventories()
    {
        return $this->hasMany(LocalMarketOrderHasInventory::class, 'local_market_order_id');
    }

    public function orderUnits()
    {
        return $this->hasMany(LocalMarketOrderHasUnit::class, 'local_market_order_id');
    }

    public function unitOwnerships()
    {
        return $this->hasMany(LocalMarketUnitOwnership::class, 'local_market_order_id');
    }

    public function histories()
    {
        return $this->hasMany(LocalMarketOrderHistory::class, 'local_market_order_id');
    }

    public function cancelOrder()
    {
        return $this->hasOne(LocalMarketOrderHasCancelReason::class, 'order_id');
    }

    public function inventoryUnits()
    {
        return $this->hasMany(LocalMarketInventoryUnits::class, 'hold_for');
    }

    public function changeStatusTo($status)
    {
        $this->status = $status;
        $this->save();
        $this->createLocalMarketOrderHistory($this, $status);
    }

    public function lender()
    {
        return $this->belongsTo(Lender::class, 'company_id');
    }

    public function markAsSettled(): void
    {
        $this->update(['is_commodities_settled' => true]);

        Log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(formatLocalMarketOrderTitle('Local market order marked as settled', $this), [
            'local_market_order_id' => $this->id,
            'external_order_no' => $this->external_order_no,
        ]);
    }

    public function isCommoditiesSettled(): bool
    {
        return $this->is_commodities_settled;
    }
}
