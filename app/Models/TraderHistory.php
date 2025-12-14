<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\VirtualColumn\VirtualColumn;

/**
 * @property TraderOrder $traderOrder
 */
class TraderHistory extends Model
{
    use HasFactory, VirtualColumn;

    protected $fillable = [
        'trader_order_id',
        'action',
        'updated_at',
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'trader_order_id',
            'action',
            'created_at',
            'updated_at',
        ];
    }

    public function traderOrder(): BelongsTo
    {
        return $this->belongsTo(TraderOrder::class, 'trader_order_id', 'id');
    }
}
