<?php

declare(strict_types=1);

return [
    'trade_request_cancelled' => [
        'label' => 'تم إلغاء طلب التداول',
    ],
    'order_cancelled' => [
        'label' => 'تم إلغاء الطلب',
        'description' => 'تم إلغاء الطلب #:order_id بواسطة :user_name. المبلغ: :amount, سعر البيع: :selling_price',
    ],
    'order_requires_approval' => [
        'label' => 'الطلب يتطلب موافقة',
    ],
    'delivery_confirmation_received' => [
        'label' => 'تم استلام تأكيد التسليم',
    ],
    'order_approved' => [
        'label' => 'تم الموافقة على الطلب',
    ],
    'invoice_paid' => [
        'label' => 'تم دفع الفاتورة',
    ],
    'lender_registered' => [
        'label' => 'تم تسجيل مقرض جديد',
    ],
    'export_ready' => [
        'label' => 'الملف جاهز',
        'description' => 'الملف :exportType جاهز للتحميل',
    ],
];
