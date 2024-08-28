<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocalMarketOrderHasUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'local_market_order_id',
        'unit_id',
        'inventory_id',
    ];

    public function inventoryUnit()
    {
        return $this->belongsTo(LocalMarketInventoryUnits::class, 'unit_id');
    }

    public function orderHasInventory()
    {
        return $this->belongsTo(LocalMarketOrderHasInventory::class, 'inventory_id');
    }
}
