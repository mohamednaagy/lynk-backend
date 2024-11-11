<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocalMarketOrderHasInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'local_market_order_id',
        'quantity',
        'price',
        'local_market_inventory_id',
        'supplier_id',
    ];

    public function inventory()
    {
        return $this->belongsTo(LocalMarketInventory::class, 'local_market_inventory_id');
    }

    public function traderOrderUnits()
    {
        return $this->hasMany(LocalMarketOrderHasUnit::class, 'order_has_inventory_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
