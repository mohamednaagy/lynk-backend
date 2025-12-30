<?php

namespace App\Models;

use App\Enums\LocalMarket\OwnershipTypes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LocalMarketInventoryUnits extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'local_market_inventory_id',
        'commodity_item_id',
        'qr_code',
        'status',
        'hold_for',
        'last_action',
        'current_owner',
        'current_owner_type',
        'last_completed_order_id',
        'last_purchasing_order_id',
        'previous_owner_type',
        'previous_owner',
    ];

    protected $casts = [
        'previous_company_id_owners' => 'array',
    ];

    public function item()
    {
        return $this->belongsTo(CommodityItem::class, 'commodity_item_id');
    }

    public function inventory()
    {
        return $this->belongsTo(LocalMarketInventory::class, 'local_market_inventory_id');
    }

    public function traderOrderUnits()
    {
        return $this->hasMany(LocalMarketOrderHasUnit::class, 'inventory_unit_id');
    }

    public function completedOrder()
    {
        return $this->belongsTo(LocalMarketOrder::class, 'last_completed_order_id');
    }

    public function getLastValidOwner(): array
    {
        if (is_null($this->last_completed_order_id)) {
            return ['current_owner' => $this->inventory->company_id, 'current_owner_type' => OwnershipTypes::OriginalSupplier];
        } else {
            return ['current_owner' => $this->completedOrder->external_order_no, 'current_owner_type' => OwnershipTypes::TraderOrder];
        }
    }
}
