<?php

declare(strict_types=1);

use App\Enums\CompanyStatus;
use App\Enums\FinancingOrderStatus;

return [
    CompanyStatus::class => [
        CompanyStatus::Pending => 'قيد الانتظار',
        CompanyStatus::UnderReview => 'قيد المراجعة',
        CompanyStatus::Approved => 'تمت الموافقة',
        CompanyStatus::Rejected => 'تم الرفض',
    ],

    FinancingOrderStatus::class => [
        FinancingOrderStatus::PendingApproval => 'بانتظار الموافقة',
        FinancingOrderStatus::Approved => 'تمت الموافقة',
        FinancingOrderStatus::Cancelled => 'تم الإلغاء',
        FinancingOrderStatus::Completed => 'مكتمل',
        FinancingOrderStatus::Rejected => 'تم الرفض',
        FinancingOrderStatus::CommodityPurchased => 'تم شراء السلعة',
        FinancingOrderStatus::CommoditySoldToCustomer => 'تم بيع السلعة للعميل',
        FinancingOrderStatus::MurabhaOfferIssued => 'تم اصدار عرض المرابحة',
        FinancingOrderStatus::MurabahaSaleCompleted => 'تم استكمال عرض المرابحة',
        FinancingOrderStatus::ContractSigned => 'تم توقيع العقد',
        FinancingOrderStatus::WaitingClientWakala => 'في انتظار وكالة العميل',
        FinancingOrderStatus::WaitingPurchasingCommodity => 'في انتظار شراء السلعة',
        FinancingOrderStatus::ClientWakalaCompleted => 'تم استكمال وكالة العميل',
        FinancingOrderStatus::RespondedToPtp => ' الرد بالوعد بالشراء',
        FinancingOrderStatus::PtpDocumentRetrieved => ' تم استرداد مستند الوعد بالشراء',
        FinancingOrderStatus::PendingCancellation => 'في انتظار الإلغاء',
    ],
];
