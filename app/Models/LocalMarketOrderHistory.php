<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocalMarketOrderHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'local_market_order_id',
        'status',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(LocalMarketOrder::class, 'local_market_order_id');
    }
}
