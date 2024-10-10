<?php

namespace App\Models;

use App\Enums\LocalMarketOrderStatus;
use App\Support\Traders\Traits\LocalMarketHelperTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed $currency
 */
class LocalMarketOrder extends Model
{
    use HasFactory , LocalMarketHelperTrait;

    protected $fillable = [
        'source',
        'amount',
        'national_id',
        'customer_name',
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
        'preferred_commodity_type',
    ];

    protected $casts = [
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

    public function inverntoryUnits()
    {
        return $this->hasMany(LocalMarketInventoryUnits::class, 'hold_for');
    }

    public function canCancelledOrder()
    {
        return
            $this->status == LocalMarketOrderStatus::EligibleCommoditiesAvailable ||
            $this->status == LocalMarketOrderStatus::PendingEligibleCommodities ||
            $this->status == LocalMarketOrderStatus::CommoditiesPurchased;
    }

    public function changeStatusTo($status)
    {
        $this->status = $status;
        $this->save();
        $this->createLocalMarketOrderHistory($this, $status);
    }
}
