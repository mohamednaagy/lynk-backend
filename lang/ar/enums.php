<?php

declare(strict_types=1);

use App\Enums\BursamProductCode;
use App\Enums\CompanyStatus;
use App\Enums\EdaatInvoiceStatus;
use App\Enums\EnquiryStatus;
use App\Enums\FinancingOrderStatus;
use App\Enums\MurabhaStep;
use App\Enums\TraderOrderRefundReason;
use App\Enums\WalletNotificationType;

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

    MurabhaStep::class => [
        MurabhaStep::TraderOrderCreated => 'إنشاء طلب مرابحة',
        MurabhaStep::PurchasingCommodity => 'شراء السلعة',
        MurabhaStep::ContractSigned => 'توقيع العقد',
        MurabhaStep::CommoditySoldToCustomer => 'بيع السلعة للعميل',
        MurabhaStep::TransferOwnershipToLender => 'نقل الملكية إلى المُقرض',
        MurabhaStep::ClientWakala => 'وكالة العميل',
        MurabhaStep::MurabhaOfferIssued => 'إصدار عرض المرابحة',
        MurabhaStep::MurabahaSaleCompleted => 'إكمال عملية المرابحة',
    ],

    BursamProductCode::class => [
        BursamProductCode::CrudePalmOil => 'زيت النخيل الخام',
        BursamProductCode::PlasticResinA => 'راتنج بلاستيك A',
        BursamProductCode::PlasticResinADev => 'راتنج بلاستيك A',
        BursamProductCode::PlasticResinB => 'راتنج بلاستيك B',
        BursamProductCode::PlasticResinBDev => 'راتنج بلاستيك B',
        BursamProductCode::PlumbumLead => 'رصاص',
        BursamProductCode::PlumbumLeadDev => 'رصاص',
        BursamProductCode::RbdPalmOlein => 'زيت الأولين',
        BursamProductCode::TimberHardwood => 'خشب صلب',
        BursamProductCode::TimberSoftwood => 'خشب لين',
    ],
    WalletNotificationType::class => [
        WalletNotificationType::ORDER_COUNT => 'الحد المتاح للطلبات',
        WalletNotificationType::WALLET_BALANCE => 'الحد المتاح للرصيد',
    ],
    TraderOrderRefundReason::class => [
        TraderOrderRefundReason::WITHIN_24_HOUR => '',
    ],
];
