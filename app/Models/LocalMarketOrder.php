<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed $currency
 */
class LocalMarketOrder extends Model
{
    use HasFactory;

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

    public function histories()
    {
        return $this->hasMany(LocalMarketOrderHistory::class, 'local_market_order_id');
    }

    public function cancelOrder()
    {
        return $this->hasOne(LocalMarketOrderHasCancelReason::class, 'order_id');
    }
}
