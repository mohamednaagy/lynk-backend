<?php

use App\Enums\WalletNotificationType;

return [
    WalletNotificationType::ORDER_COUNT => [
        'subject' => 'Wallet Orders Available Below Threshold',
        'content' => 'Your Wallet order available has reached the threshold of :value. Please recharge your Wallet.',
    ],
    WalletNotificationType::WALLET_BALANCE => [
        'subject' => 'Wallet Amount Available Below Threshold',
        'content' => 'Your Wallet has reached the threshold of :value. Please recharge your Wallet.',
    ],
    'action' => 'View Wallet',
];
