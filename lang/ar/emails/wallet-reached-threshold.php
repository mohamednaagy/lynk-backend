<?php

use App\Enums\WalletNotificationType;

return [
    WalletNotificationType::ORDER_COUNT => [
        'subject' => 'طلبات المتاحة في المحفظة تحت الحد الأدنى',
        'content' => 'لقد وصلت طلبات المتوفرة في المحفظة لديك إلى الحد الأدنى :value. يرجى إعادة شحن محفظتك.',
    ],
    WalletNotificationType::WALLET_BALANCE => [
        'subject' => 'رصيد المحفظة تحت الحد الأدنى',
        'content' => 'لقد وصل رصيد المحفظة لديك إلى الحد الأدنى :value. يرجى إعادة شحن محفظتك.',
    ],
    'action' => 'عرض المحفظة',
];
