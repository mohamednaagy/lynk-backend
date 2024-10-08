<?php

use App\Enums\TraderOrderCancelReason;
use Illuminate\Support\Facades\Config;

return [
    'user_cancel_request' => 'User has chosen to cancel this trade request',
    'user_cancel_order' => 'User has chosen to cancel this order',
    'trader' => [
        'cancel_message' => [
            TraderOrderCancelReason::FinancingOrderIsCancelled => 'Order cancelled by user',
            TraderOrderCancelReason::TraderOrderIsCancelled => 'Trade request cancelled by user ',
            TraderOrderCancelReason::NoEligibleCommoditiesAvailable => 'No commodities found with LOCAL Trader.',
        ],
        'bursa' => [
            'hold_status' => 'Trade Request on hold due to International Trader (Bursa Malaysia) Market Cut-Off Time until '.Config::get('services.bursam.market_opening_start_time').' pm KSA time.',
        ],
        'lynk' => [
            'cancelled_status' => 'User has chosen to cancel this trade request.',
            'no_commodity_available' => 'No commodities found with LOCAL Trader.',
            'internal_technical_error' => 'Internal Technical Error',

        ],
    ],
];
