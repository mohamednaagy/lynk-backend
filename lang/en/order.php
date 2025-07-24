<?php

return [
    'user_cancel_request' => 'User has chosen to cancel this trade request.',
    'user_cancel_order' => 'User has chosen to cancel this order.',
    'murabaha_time_out' => 'Trade Request cancelled due to Market Close Time',
    'no_eligible_commodities_available' => 'No commodities found with Trader',
    'trader' => [
        'bursa' => [
            'hold_status' => 'Trade Request on hold due to International Trader (Bursa Malaysia) Market Cut-Off Time until :TIME KSA time.',
            'steps' => [
                'contract_signed' => [
                    'v2' => [
                        'proceed' => 'Contract Signed confirmed by user.',
                        'wakalaAndSell' => 'Contract Signed and Client Wakala - SELL confirmed by user.',
                    ],
                ],
                'client_wakala' => [
                    'v2' => [
                        'sell' => 'Client Wakala - Sell Customer confirmed by user.',
                        'wakalaAndSell' => '',
                    ],
                ],
            ],
        ],
        'lynk' => [
            'cancelled_status' => 'User has chosen to cancel this trade request.',
            'no_commodity_available' => 'No commodities found with Trader.',
            'internal_technical_error' => 'Internal Technical Error',
            'expired_contract_time' => 'Contract Sign Time Limit of :TIME hours has expired.',
            'expired_confirmation_time_limit' => 'Trade request cancelled by system due to Customer Delivery Confirmation Time Limit of :TIME hours has expired.',
            'steps' => [
                'contract_signed' => [
                    'v1' => [
                        'sell' => 'Contract Signed - Sell for Customer confirmed by user.',
                        'deliver' => 'Contract Signed - Delivery for Customer requested by user.',
                    ],
                    'v2' => [
                        'proceed' => 'Contract Signed confirmed by user.',
                        'wakalaAndSell' => 'Contract Signed and Client Wakala - SELL confirmed by user.',
                    ],
                ],
                'client_wakala' => [
                    'v2' => [
                        'sell' => 'Client Wakala - Sell Customer confirmed by user.',
                        'deliver' => 'Client Wakala - Deliver for Customer confirmed by user. Please contact a LYNK Administrator to confirm delivery process and logistics',
                        'wakalaAndSell' => '',
                    ],
                ],
                'customer_delivery_confirmation' => [
                    'delivery_not_applicable' => 'Contract Signed - Sell confirmed. Delivery Not Applicable.',
                    'pending' => 'Customer Delivery Confirmation - Delivery for customer pending confirmation by the client. Please contact the client to confirm delivery or ignore and sell.',
                    'IgnoreAndSell' => 'Customer Delivery Confirmation - Cancel delivery and sell for the customer confirmed by the user.',
                    'DeliveryConfirmed' => 'Customer Delivery Confirmation - Delivery for the customer confirmed by the user. Please contact a LYNK Administrator to confirm the delivery process and logistics.',
                ],
            ],
        ],
    ],
];
