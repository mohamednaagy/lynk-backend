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
            'no_commodity_available' => 'No commodities found with LOCAL Trader.',
            'internal_technical_error' => 'Internal Technical Error',
            'steps' => [
                'contract_signed' => [
                    'sell' => 'Contract Signed - Sell for Customer confirmed by user.',
                    'deliver' => 'Contract Signed - Delivery for Customer requested by user.',
                ],
                'customer_delivery_confirmation' => [
                    'pending' => 'Customer Delivery Confirmation - Delivery for customer pending confirmation by the client. Please contact the client to confirm delivery or ignore and sell.',
                    'IgnoreAndSell' => 'Customer Delivery Confirmation - Cancel delivery and sell for the customer confirmed by the user.',
                    'DeliveryConfirmed' => 'Customer Delivery Confirmation - Delivery for the customer confirmed by the user. Please contact a LYNK Administrator to confirm the delivery process and logistics.',
                ],
            ],
        ],
    ],
];
