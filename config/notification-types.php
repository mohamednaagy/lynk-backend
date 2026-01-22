<?php

declare(strict_types=1);

use App\Enums\NotificationChannel;
use App\Enums\Role;
use App\Enums\SystemNotificationType;

return [
    SystemNotificationType::TRADE_REQUEST_CANCELLED->value => [
        'label' => 'notification-types.trade_request_cancelled.label',
        'channels' => [
            NotificationChannel::PLATFORM->value => [
                'default' => false,
                'is_editable' => false,
            ],
            NotificationChannel::MAIL->value => [
                'default' => false,
                'is_editable' => true,
            ],
        ],
        'roles' => [
            Role::Admin,
            Role::Manager,
            Role::LenderAdmin,
        ],
    ],
    SystemNotificationType::ORDER_CANCELLED->value => [
        'label' => 'notification-types.order_cancelled.label',
        'channels' => [
            NotificationChannel::PLATFORM->value => [
                'default' => false,
                'is_editable' => false,
            ],
            NotificationChannel::MAIL->value => [
                'default' => false,
                'is_editable' => true,
            ],
        ],
        'roles' => [
            Role::Admin,
            Role::LenderAdmin,
        ],
    ],
    SystemNotificationType::ORDER_REQUIRES_APPROVAL->value => [
        'label' => 'notification-types.order_requires_approval.label',
        'channels' => [
            NotificationChannel::PLATFORM->value => [
                'default' => false,
                'is_editable' => false,
            ],
            NotificationChannel::MAIL->value => [
                'default' => false,
                'is_editable' => true,
            ],
        ],
        'roles' => [
            Role::Admin,
            Role::LenderAdmin,
        ],
    ],
    SystemNotificationType::DELIVERY_CONFIRMATION_RECEIVED->value => [
        'label' => 'notification-types.delivery_confirmation_received.label',
        'channels' => [
            NotificationChannel::PLATFORM->value => [
                'default' => false,
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
    SystemNotificationType::ORDER_APPROVED->value => [
        'label' => 'notification-types.order_approved.label',
        'channels' => [
            NotificationChannel::PLATFORM->value => [
                'default' => false,
                'is_editable' => false,
            ],
            NotificationChannel::MAIL->value => [
                'default' => false,
                'is_editable' => true,
            ],
        ],
        'roles' => [
            Role::Admin,
            Role::Manager,
            Role::LenderAdmin,
            Role::LenderApiUser,
            Role::LenderSupervisor,
            Role::LenderOrderCreator,
        ],
    ],
    SystemNotificationType::LENDER_REGISTERED->value => [
        'label' => 'notification-types.lender_registered.label',
        'channels' => [
            NotificationChannel::PLATFORM->value => [
                'default' => false,
                'is_editable' => false,
            ],
            NotificationChannel::MAIL->value => [
                'default' => false,
                'is_editable' => true,
            ],
        ],
        'roles' => [
            Role::Admin,
            Role::Manager,
        ],
    ],
    SystemNotificationType::ORDERS_REPORT_EXPORT_READY->value => [
        'label' => 'notification-types.orders_report_export_ready.label',
        'channels' => [
            NotificationChannel::PLATFORM->value => [
                'default' => true,
                'is_editable' => false,
            ],
            NotificationChannel::MAIL->value => [
                'default' => false,
                'is_editable' => false,
            ],
        ],
        'roles' => [
            Role::Admin,
            Role::Manager,
        ],
    ],
];
