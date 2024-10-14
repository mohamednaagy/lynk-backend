<?php

use App\Enums\Role;
use App\Http\Controllers\Api\V1\Lender\Orders\OrderController;

return [
    OrderController::class => [
        'show' => [
            Role::LenderApiUser => [
                'trader_orders.provider',
                'trader_orders.mode',
            ],
        ],
    ],

];
