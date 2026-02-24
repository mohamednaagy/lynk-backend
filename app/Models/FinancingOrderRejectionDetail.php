<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FinancingOrderRejectionReason;
use App\Traits\HasCreator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancingOrderRejectionDetail extends Model
{
    use HasCreator, HasFactory;

    protected $fillable = [
        'financing_order_id',
        'creator_id',
        'rejection_reason',
        'comment',
    ];

    protected $casts = [
        'rejection_reason' => FinancingOrderRejectionReason::class,
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
