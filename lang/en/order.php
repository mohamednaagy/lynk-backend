<?php

use Illuminate\Support\Facades\Config;

return [
    'user_cancel_request' => 'User has chosen to cancel this trade request',
    'user_cancel_order' => 'User has chosen to cancel this order',

    'trader' => [
        'bursa' => [
            'hold_status' => 'Trade Request on hold due to International Trader (Bursa Malaysia) Market Cut-Off Time until :TIME KSA time.',
        ],
        'lynk' => [
            'cancelled_status' => 'User has chosen to cancel this trade request.',
        ],
    ],
];
