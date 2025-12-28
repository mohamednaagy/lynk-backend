<?php

use App\Enums\NotificationChannel;
use App\Enums\Role;
use App\Enums\SystemNotificationType;

return [
    SystemNotificationType::TRADE_REQUEST_CANCELLED->value => [
        'label' => 'notifications.trade_request_cancelled.label',
        'channels' => [
            NotificationChannel::PLATFORM->value => [
                'default' => true,
                'is_editable' => false,
            ],
            NotificationChannel::MAIL->value => [
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
    SystemNotificationType::ORDER_CANCELLED->value => [
        'label' => 'notifications.order_cancelled.label',
        'channels' => [
            NotificationChannel::PLATFORM->value => [
                'default' => true,
                'is_editable' => false,
            ],
            NotificationChannel::MAIL->value => [
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
    SystemNotificationType::ORDER_REQUIRES_APPROVAL->value => [
        'label' => 'notifications.order_requires_approval.label',
        'channels' => [
            NotificationChannel::PLATFORM->value => [
                'default' => true,
                'is_editable' => false,
            ],
            NotificationChannel::MAIL->value => [
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
    SystemNotificationType::DELIVERY_CONFIRMATION_RECEIVED->value => [
        'label' => 'notifications.delivery_confirmation_received.label',
        'channels' => [
            NotificationChannel::PLATFORM->value => [
                'default' => true,
                'is_editable' => false,
            ],
            NotificationChannel::MAIL->value => [
                'default' => false,
                'is_editable' => true,
            ],
        ],
        'roles' => [
            Role::Admin,
        ],
    ],
    SystemNotificationType::ORDER_CREATED->value => [
        'label' => 'notifications.order_created.label',
        'channels' => [
            NotificationChannel::PLATFORM->value => [
                'default' => true,
                'is_editable' => false,
            ],
            NotificationChannel::MAIL->value => [
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
];
