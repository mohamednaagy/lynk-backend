<?php

declare(strict_types=1);

return [
    'trade_request_cancelled' => [
        'label' => 'Trade Request Cancelled',
        'description' => 'Trade request #:trader_order_id for order #:order_id has been cancelled by :user_name. Amount: :amount, Selling Price: :selling_price',
    ],
    'order_cancelled' => [
        'label' => 'Order Cancelled',
        'description' => 'Order #:order_id has been cancelled by :user_name. Amount: :amount, Selling Price: :selling_price',
    ],
    'order_requires_approval' => [
        'label' => 'Order Requires Approval',
        'description' => 'Order #:order_id requires your approval. Amount: :amount, Selling Price: :selling_price',
    ],
    'delivery_confirmation_received' => [
        'label' => 'Delivery Confirmation Received',
        'description' => 'Delivery confirmation received for order #:order_id with trader reference #:trader_reference',
    ],
    'order_approved' => [
        'label' => 'Order Approved',
        'description' => 'Order #:order_id has been approved by :approver_name at :approved_at',
    ],
    'invoice_paid' => [
        'label' => 'Invoice Paid',
    ],
    'lender_registered' => [
        'label' => 'New Lender Registered',
        'description' => 'A new lender has been registered: :company_name',
    ],
    'orders_report_export_ready' => [
        'label' => 'Order List Export Ready',
        'description' => 'Your export file is ready to download.',
    ],
];
