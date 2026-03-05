<?php

declare(strict_types=1);

return [
    'trade_request_cancelled' => [
        'label' => 'تم إلغاء طلب التداول',
        'description' => 'تم إلغاء طلب المرابحة #:trader_order_id للطلب #:order_id بواسطة :user_name. المبلغ: :amount, سعر البيع: :selling_price',
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
        'description' => 'قامت :company_name بتأكيد التسليم للطلب #:order_id',
        'action_text' => 'طلب #:order_id',
    ],
    'order_approved' => [
        'label' => 'تم الموافقة على الطلب',
        'description' => 'تم الموافقة على الطلب #:order_id بواسطة :approver_name في :approved_at',
    ],
    'invoice_paid' => [
        'label' => 'تم دفع الفاتورة',
    ],
    'lender_registered' => [
        'label' => 'تم تسجيل مقرض جديد',
        'description' => 'تم تسجيل مقرض جديد: :company_name',
    ],
    'orders_report_export_ready' => [
        'label' => 'تقرير الطلبات جاهز للتحميل',
        'description' => 'الملف جاهز للتحميل',
    ],
    'trade_request_expired' => [
        'label' => 'انتهت صلاحية طلب التداول',
        'description' => 'انتهت صلاحية طلب المرابحة #:trader_order_id للطلب #:order_id .',
    ],
    'in_progress_orders' => [
        'label' => 'الطلبات قيد التنفيذ',
        'description' => 'لدى :company_name طلب/طلبات قيد التنفيذ.',
    ],
    'wallet_report_export_ready' => [
        'label' => 'تقرير المحفظة جاهز للتحميل',
        'description' => 'الملف جاهز للتحميل',
    ],
];
