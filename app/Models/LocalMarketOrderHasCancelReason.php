<?php

namespace App\Models;

use App\Enums\LocalMarket\OrderCancelReason;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocalMarketOrderHasCancelReason extends Model
{
    use HasFactory;

    protected $fillable = [
        'cancelled_by',
        'cancel_reason',
        'cancel_step',
        'order_id',
        'cancel_type',
    ];

    protected $casts = [
        'cancel_reason' => OrderCancelReason::class,
    ];

    public function order()
    {
        return $this->belongsTo(LocalMarketOrder::class, 'order_id');
    }
}
