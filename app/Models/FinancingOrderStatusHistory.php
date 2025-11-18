<?php

namespace App\Models;

use App\Traits\HasCreator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancingOrderStatusHistory extends Model
{
    use HasCreator;

    protected $table = 'financing_order_histories';

    protected $fillable = [
        'order_id',
        'status',
        'creator_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(FinancingOrder::class, 'order_id');
    }
}
