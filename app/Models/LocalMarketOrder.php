<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocalMarketOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'source',
        'amount',
        'national_id',
        'price',
        'customer_name',
        'status',
        'comment',
        'company_id',
        'trader_order_id',
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
