<?php

declare(strict_types=1);

return [
    'trade_request_cancelled' => [
        'label' => 'Trade Request Cancelled',
    ],
    'order_cancelled' => [
        'label' => 'Order Cancelled',
        'description' => 'Order #:order_id has been cancelled by :user_name. Amount: :amount, Selling Price: :selling_price',
    ],
    'order_requires_approval' => [
        'label' => 'Order Requires Approval',
    ],
    'delivery_confirmation_received' => [
        'label' => 'Delivery Confirmation Received',
    ],
    'order_approved' => [
        'label' => 'Order Approved',
    ],
    'invoice_paid' => [
        'label' => 'Invoice Paid',
    ],
    'lender_registered' => [
        'label' => 'New Lender Registered',
    ],
    'export_ready' => [
        'label' => 'Export Ready',
        'description' => 'Your :exportType export is ready for download.',
    ],
];
