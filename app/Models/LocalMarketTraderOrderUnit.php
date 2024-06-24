<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocalMarketTraderOrderUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_unit_id',
        'order_has_inventory_id',
    ];

    public function inventoryUnit()
    {
        return $this->belongsTo(LocalMarketInventoryUnits::class, 'inventory_unit_id');
    }

    public function orderHasInventory()
    {
        return $this->belongsTo(LocalMarketOrderHasInventory::class, 'order_has_inventory_id');
    }
}
