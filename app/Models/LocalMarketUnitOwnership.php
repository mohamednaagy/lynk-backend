<?php

namespace App\Models;

use App\Enums\LocalMarket\UnitOwnershipAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocalMarketUnitOwnership extends Model
{
    use HasFactory;

    protected $table = 'local_market_unit_ownership';

    protected $fillable = [
        'local_market_order_id',
        'unit_id',
        'owner_type',
        'current_owner',
        'current_owner_type',
        'previous_owner',
        'previous_owner_type',
        'action',
        'created_at',
        'updated_at',
    ];

    public $timestamps = true;

    protected $casts = [
        'action' => UnitOwnershipAction::class,
    ];
}
