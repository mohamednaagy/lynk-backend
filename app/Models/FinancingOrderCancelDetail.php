<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FinancingOrderCancelReason;
use App\Traits\HasCreator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancingOrderCancelDetail extends Model
{
    use HasCreator, HasFactory;

    protected $fillable = [
        'financing_order_id',
        'creator_id',
        'cancel_reason',
        'comment',
    ];

    protected $casts = [
        'cancel_reason' => FinancingOrderCancelReason::class,
    ];

    public function financingOrder()
    {
        return $this->belongsTo(FinancingOrder::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
