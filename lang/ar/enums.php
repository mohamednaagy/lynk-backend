<?php

declare(strict_types=1);

use App\Enums\BursamMurabhaStep;
use App\Enums\CompanyStatus;
use App\Enums\DmccMurabhaStep;
use App\Enums\EdaatInvoiceStatus;
use App\Enums\EnquiryStatus;
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
        FinancingOrderStatus::InProgress => 'قيد التنفيذ',
        //        FinancingOrderStatus::CommodityPurchased => 'تم شراء السلعة',
        //        FinancingOrderStatus::CommoditySoldToCustomer => 'تم بيع السلعة للعميل',
        //        FinancingOrderStatus::MurabhaOfferIssued => 'تم اصدار عرض المرابحة',
        //        FinancingOrderStatus::MurabahaSaleCompleted => 'تم استكمال عرض المرابحة',
        //        FinancingOrderStatus::ContractSigned => 'تم توقيع العقد',
        //        FinancingOrderStatus::WaitingClientWakala => 'في انتظار وكالة العميل',
        //        FinancingOrderStatus::WaitingPurchasingCommodity => 'في انتظار شراء السلعة',
        //        FinancingOrderStatus::ClientWakalaCompleted => 'تم استكمال وكالة العميل',
        //        FinancingOrderStatus::RespondedToPtp => ' الرد بالوعد بالشراء',
        //        FinancingOrderStatus::PtpDocumentRetrieved => ' تم استرداد مستند الوعد بالشراء',
        FinancingOrderStatus::PendingCancellation => 'في انتظار الإلغاء',
    ],

    EnquiryStatus::class => [
        EnquiryStatus::UnderReview => 'قيد المراجعة',
        EnquiryStatus::Resolved => 'تم الحل',
        EnquiryStatus::Closed => 'مغلق',
    ],

    EdaatInvoiceStatus::class => [
        EdaatInvoiceStatus::Pending => 'قيد الانتظار',
        EdaatInvoiceStatus::Paid => 'مدفوعة',
        EdaatInvoiceStatus::Expired => 'منتهية الصلاحية',
    ],

    BursamMurabhaStep::class => [

        BursamMurabhaStep::TraderOrderCreated => 'إنشاء طلب مرابحة',
        BursamMurabhaStep::PurchasingCommodity => 'شراء السلعة',
        BursamMurabhaStep::ContractSigned => 'توقيع العقد',
        BursamMurabhaStep::CommoditySoldToCustomer => 'بيع السلعة للعميل',
        BursamMurabhaStep::TransferOwnershipToLender => 'نقل الملكية إلى المُقرض',
        BursamMurabhaStep::ClientWakala => 'وكالة العميل',
        BursamMurabhaStep::MurabhaOfferIssued => 'إصدار عرض المرابحة',
        BursamMurabhaStep::MurabahaSaleCompleted => 'إكمال عملية المرابحة',
    ],

    DmccMurabhaStep::class => [
        DmccMurabhaStep::TraderOrderCreated => 'إنشاء طلب مرابحة',
        DmccMurabhaStep::PurchasingCommodity => 'شراء السلعة',
        DmccMurabhaStep::ContractSigned => 'توقيع العقد',
        DmccMurabhaStep::CommoditySoldToCustomer => 'بيع السلعة للعميل',
        DmccMurabhaStep::ClientWakala => 'وكالة العميل',
        DmccMurabhaStep::MurabhaOfferIssued => 'إصدار عرض المرابحة',
        DmccMurabhaStep::MurabahaSaleCompleted => 'إكمال عملية المرابحة',
    ],
];
