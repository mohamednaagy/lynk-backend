<?php

namespace App\Models;

use App\Enums\LocalMarketOrderStatus;
use App\Support\Money\Casts\MoneyStringCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed $currency
 * @property mixed $amount
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
        'trader_order_id',
        'reference',
        'buying_uuid',
        'selling_uuid',
    ];

    protected $casts = [
        'status' => LocalMarketOrderStatus::class,
        'amount' => MoneyStringCast::class.':currency',
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
