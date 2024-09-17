<?php

use Illuminate\Support\Facades\Config;

return [
    'user_cancel_request' => 'User has chosen to cancel this trade request',
    'user_cancel_order' => 'User has chosen to cancel this order',
    'trader' => [
        'bursa' => [
            'hold_status' => 'Trade Request on hold due to International Trader (Bursa Malaysia) Market Cut-Off Time until '.Config::get('services.bursam.market_opening_start_time').' pm KSA time.',
        ],
        'lynk' => [
            'cancelled_status' => 'User has chosen to cancel this trade request.',
            'no_commodity_available' => 'No commodities found with LOCAL Trader.',
            'internal_technical_error' => 'Internal Technical Error',
            'steps' => [
                'contract_signed' => [
                    'sell' => 'Contract Signed - Sell for Customer confirmed by user.',
                    'deliver' => 'Contract Signed - Delivery for Customer requested by user.',
                ],
            ],
        ],
    ],
];
