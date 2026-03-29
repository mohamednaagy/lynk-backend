<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Watson\Rememberable\Rememberable;

class TraderOrderDuration extends Model
{
    use Rememberable;

    protected $rememberCacheTag = 'trader_order_durations';

    protected $rememberCachePrefix = 'trader_order_durations';

    protected $rememberFor = 60 * 60 * 24;

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
