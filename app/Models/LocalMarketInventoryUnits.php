<?php

namespace App\Models;

use App\Enums\LocalMarket\OwnershipTypes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LocalMarketInventoryUnits extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'inventory_id',
        'commodity_item_id',
        'qr_code',
        'status',
        'hold_for',
        'current_owner',
        'current_owner_type',
        'last_completed_order_id',
        'previous_owner_type',
        'previous_owner',
    ];

    protected $casts = [
        'previous_owners' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }

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

    public static function insertBulk($data)
    {
        $now = saudi_now('Y-m-d h:i:s');
        // Implement bulk insertion logic here
        $data = array_map(function ($item) use ($now) {
            return array_merge($item, [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }, $data);

        DB::table((new static)->getTable())->insert($data);
    }

    /**
     * Add a new owner to the previous owners list, keeping only the last 10 owner IDs
     */
    public function addPreviousOwner(int $ownerId): void
    {
        $previousOwners = $this->previous_owners ?? [];
        $previousOwners[] = $ownerId;

        // Keep only the last 10 owners
        if (count($previousOwners) > 10) {
            $previousOwners = array_slice($previousOwners, -10);
        }

        $this->previous_owners = $previousOwners;
        $this->save();
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
