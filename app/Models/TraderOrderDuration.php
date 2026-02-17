<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TraderOrderDuration extends Model
{
    protected $fillable = [
        'trader_order_id',
        'purchasing_commodity',
        'contract_signed',
        'commodity_sold_to_customer',
        'client_wakala',
        'murabha_offer_issued',
        'murabaha_sale_completed',
        'customer_delivery_confirmation',
    ];

    public function traderOrder(): BelongsTo
    {
        return $this->belongsTo(TraderOrder::class, 'trader_order_id', 'id');
    }
}
