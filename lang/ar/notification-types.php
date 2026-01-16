<?php

declare(strict_types=1);

return [
    'trade_request_cancelled' => [
        'label' => 'تم إلغاء طلب التداول',
        'description' => 'تم إلغاء طلب التداول #:trader_order_id للطلب #:order_id بواسطة :user_name. المبلغ: :amount, سعر البيع: :selling_price',
    ],
    'order_cancelled' => [
        'label' => 'تم إلغاء الطلب',
        'description' => 'تم إلغاء الطلب #:order_id بواسطة :user_name. المبلغ: :amount, سعر البيع: :selling_price',
    ],
    'order_requires_approval' => [
        'label' => 'الطلب يتطلب موافقة',
        'description' => 'الطلب #:order_id يتطلب موافقة الموقع. المبلغ: :amount, سعر البيع: :selling_price',
    ],
    'delivery_confirmation_received' => [
        'label' => 'تم استلام تأكيد التسليم',
        'description' => 'تم استلام تأكيد التسليم للطلب #:order_id بواسطة :trader_reference',
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
