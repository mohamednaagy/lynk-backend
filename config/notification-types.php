<?php

use App\Enums\NotificationChannel;
use App\Enums\Role;
use App\Enums\SystemNotificationType;

return [
    SystemNotificationType::TRADE_REQUEST_CANCELLED => [
        'label' => 'notifications.trade_request_cancelled.label',
        'channels' => [
            NotificationChannel::platform => [
                'default' => true,
                'is_editable' => false,
            ],
            NotificationChannel::mail => [
                'default' => false,
                'is_editable' => true,
            ],
        ],
        'roles' => [
            Role::LenderAdmin,
            Role::LenderOrderCreator,
            Role::Admin,
        ],
    ],
    SystemNotificationType::ORDER_CANCELLED => [
        'label' => 'notifications.order_cancelled.label',
        'channels' => [
            NotificationChannel::platform => [
                'default' => true,
                'is_editable' => false,
            ],
            NotificationChannel::mail => [
                'default' => false,
                'is_editable' => true,
            ],
        ],
        'roles' => [
            Role::LenderAdmin,
            Role::LenderOrderCreator,
            Role::Admin,
        ],
    ],
    SystemNotificationType::ORDER_REQUIRES_APPROVAL => [
        'label' => 'notifications.order_requires_approval.label',
        'channels' => [
            NotificationChannel::platform => [
                'default' => true,
                'is_editable' => false,
            ],
            NotificationChannel::mail => [
                'default' => false,
                'is_editable' => true,
            ],
        ],
        'roles' => [
            Role::LenderAdmin,
            Role::LenderSupervisor,
            Role::Admin,
        ],
    ],
    SystemNotificationType::DELIVERY_CONFIRMATION_RECEIVED => [
        'label' => 'notifications.delivery_confirmation_received.label',
        'channels' => [
            NotificationChannel::platform => [
                'default' => true,
                'is_editable' => false,
            ],
            NotificationChannel::mail => [
                'default' => false,
                'is_editable' => true,
            ],
        ],
        'roles' => [
            Role::Admin,
        ],
    ],
];
