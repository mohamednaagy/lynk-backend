<?php

use Illuminate\Support\Facades\Config;

return [
    'user_cancel_request' => 'لقد اختار المستخدم إلغاء طلب التجارة هذا',
    'user_cancel_order' => 'لقد اختار المستخدم إلغاء هذا الطلب',

    'trader' => [
        'bursa' => [
            'hold_status' => 'طلب التداول معلق بسبب الموعد النهائي لسوق المتداول الدولي (بورصة ماليزيا) حتى '.Config::get('services.bursam.market_opening_start_time').' مساءً بتوقيت المملكة العربية السعودية.',

        ],
        'lynk' => [
            'cancelled_status' => 'اختار المستخدم إلغاء طلب التجارة هذا.',
        ],
    ],

];
