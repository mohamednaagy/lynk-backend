<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanCoverageHistory extends Model
{
    protected $guarded = [];

    protected $casts = [
        'selected_inventories' => 'array',
        'suggested_inventories' => 'array',
    ];

    public static function log(LocalMarketOrder $localMarketOrder, string $status, string $elapsedTime, array $selectedInventories, array $suggestedInventories, string $strategy, ?string $message = ''): void
    {
        self::create([
            'local_market_order_id' => $localMarketOrder->id,
            'loan' => $localMarketOrder->amount,
            'status' => $status,
            'elapsed_time' => $elapsedTime,
            'selected_inventories' => $selectedInventories,
            'suggested_inventories' => $suggestedInventories,
            'strategy' => $strategy,
            'error_msg' => $message,
        ]);
    }
}
