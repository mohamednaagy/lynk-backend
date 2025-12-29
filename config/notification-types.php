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
            Role::LenderAdmin,
            Role::LenderOrderCreator,
            Role::Admin,
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
            Role::LenderAdmin,
            Role::LenderOrderCreator,
            Role::Admin,
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
            Role::LenderAdmin,
            Role::LenderSupervisor,
            Role::Admin,
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
    SystemNotificationType::ORDER_CREATED->value => [
        'label' => 'notification-types.order_created.label',
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
            Role::LenderAdmin,
            Role::LenderOrderCreator,
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
            Role::LenderAdmin,
            Role::LenderOrderCreator,
            Role::Admin,
        ],
    ],
    SystemNotificationType::INVOICE_PAID->value => [
        'label' => 'notification-types.invoice_paid.label',
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
            Role::LenderAdmin,
            Role::LenderOrderCreator,
            Role::Admin,
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
        ],
    ],
    SystemNotificationType::NEW_SIGN_IN->value => [
        'label' => 'notification-types.new_sign_in.label',
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
            Role::LenderOrderCreator,
        ],
    ],
];
