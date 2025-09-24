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
        'commodities_settlement_status',
    ];

    protected $attributes = [
        'status' => OrderStatus::initiate,
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

    public static function changeCommoditiesSettlementStatus(int $localMarketOrderId, string $status): void
    {
        LocalMarketOrder::whereId($localMarketOrderId)->update([
            'commodities_settlement_status' => $status,
        ]);

        Log::channel(LOG_CHANNEL_LOCAL_MARKET)->info('commodities_settlement_status Changed local_market_order_id => '.$localMarketOrderId, [
            'localMarketOrderId' => $localMarketOrderId,
            'status' => $status,
        ]);
    }
}
