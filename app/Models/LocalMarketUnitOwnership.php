<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocalMarketUnitOwnership extends Model
{
    use HasFactory;

    protected $table = 'local_market_unit_ownership';

    protected $fillable = [
        'unit_id',
        'owner_type',
        'current_owner_type',
        'previous_owner',
        'previous_owner_type',
        'created_at',
        'updated_at',
    ];

    public $timestamps = true;
}
