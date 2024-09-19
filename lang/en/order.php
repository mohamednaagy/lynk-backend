<?php

return [
    'user_cancel_request' => 'User has chosen to cancel this trade request',
    'user_cancel_order' => 'User has chosen to cancel this order',
    'trader' => [
        'lynk' => [
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
