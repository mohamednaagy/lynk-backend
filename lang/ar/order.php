<?php

use App\Enums\TraderOrderCancelReason;
use Illuminate\Support\Facades\Config;

return [
    'user_cancel_request' => 'لقد اختار المستخدم إلغاء طلب التجارة هذا',
    'user_cancel_order' => 'لقد اختار المستخدم إلغاء هذا الطلب',
    'trader' => [
        'cancel_message' => [
            TraderOrderCancelReason::FinancingOrderIsCancelled => 'لقد اختار المستخدم إلغاء طلب التجارة هذا',
            TraderOrderCancelReason::TraderOrderIsCancelled => 'لقد اختار المستخدم إلغاء هذا الطلب',
            TraderOrderCancelReason::NoEligibleCommoditiesAvailable => 'لا يوجد سلع كافيه داخل السوق المحلي',
        ],

        'bursa' => [
            'hold_status' => 'طلب التداول معلق بسبب الموعد النهائي لسوق المتداول الدولي (بورصة ماليزيا) حتى '.Config::get('services.bursam.market_opening_start_time').' مساءً بتوقيت المملكة العربية السعودية.',
        ],
        'lynk' => [
            'cancelled_status' => 'اختار المستخدم إلغاء طلب التجارة هذا.',
            'no_commodity_available' => 'لا يوجد سلع كافيه داخل السوق المحلي',
            'internal_technical_error' => 'يوجد خطا ما',
        ],
    ],

];
