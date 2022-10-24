<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\VirtualColumn\VirtualColumn;

class TraderHistory extends Model
{
    use HasFactory, VirtualColumn;

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'order_id',
            'action',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(FinancingOrder::class, 'id', 'order_id');
    }
}
