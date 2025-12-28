<?php

declare(strict_types=1);

use App\Enums\BursamProductCode;
use App\Enums\CommodityTypeStatus;
use App\Enums\CommoitySupplierMarketType;
use App\Enums\CommoitySupplierStatus;
use App\Enums\CompanyLenderClientType;
use App\Enums\CompanyMarketType;
use App\Enums\CompanyStatus;
use App\Enums\EdaatInvoiceStatus;
use App\Enums\EnquiryStatus;
use App\Enums\FinancingOrderStatus;
use App\Enums\FinancingOrderTypeEnum;
use App\Enums\LocalMarket\UnitOwnershipAction;
use App\Enums\MurabhaStep;
use App\Enums\SystemNotificationType;
use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderNoRefundReason;
use App\Enums\TraderOrderRefundReason;
use App\Enums\TraderOrderStatus;
use App\Enums\TraderOrderTimeLimitType;
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

    CommoitySupplierStatus::class => [
        CommoitySupplierStatus::Active => 'مفعل',
        CommoitySupplierStatus::Inactive => 'غير مفعل',
    ],
    CommoitySupplierMarketType::class => [
        CommoitySupplierMarketType::Local => 'محلي',
        //        CommoitySupplierMarketType::International => 'دولي',
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
        BursamProductCode::Aluminium => 'الومنيوم',
        BursamProductCode::AluminiumDev => 'الومنيوم',
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
        TraderOrderRefundReason::WITHIN_24_HOUR => 'تم استرداد المبلغ لأنه تم إلغاؤه قبل 24 ساعة من :base_tr',
        TraderOrderRefundReason::WITHIN_72_HOUR => 'تم استرداد المبلغ لأنه تم إلغاؤه قبل 72 ساعة من :base_tr',
    ],
    TraderOrderNoRefundReason::class => [
        TraderOrderNoRefundReason::AFTER_24_HOUR => 'لم يتم استرداد المبلغ لأنه تم إلغاؤه بعد 24 ساعة من :base_tr',
        TraderOrderNoRefundReason::AFTER_72_HOUR => 'لم يتم استرداد المبلغ لأنه تم إلغاؤه بعد 72 ساعة من :base_tr',
    ],

    CompanyMarketType::class => [
        CompanyMarketType::Local => 'محلي',
        CompanyMarketType::International => 'عالمي',
        CompanyMarketType::Any => 'كلاهما',
    ],
    CommodityTypeStatus::class => [
        CommodityTypeStatus::Active => 'مفعل',
        CommodityTypeStatus::Inactive => 'غير مفعل',
    ],

    TraderOrderStatus::class => [
        TraderOrderStatus::Hold => 'معلق',
    ],

    SystemNotificationType::TRADE_REQUEST_CANCELLED->value => 'تم إلغاء طلب التداول',
    SystemNotificationType::ORDER_CANCELLED->value => 'تم إلغاء الطلب',
    SystemNotificationType::ORDER_REQUIRES_APPROVAL->value => 'الطلب يتطلب موافقة',
    SystemNotificationType::DELIVERY_CONFIRMATION_RECEIVED->value => 'تم استلام تأكيد التسليم',

    TraderOrderCancelReason::class => [
        TraderOrderCancelReason::Manual => '',
        TraderOrderCancelReason::MurabhaTimeout => 'تم إلغاء طلب التداول بسبب وقت إغلاق السوق',
        TraderOrderCancelReason::FailureToPurchase => '',
        TraderOrderCancelReason::FinancingOrderIsCancelled => 'لقد اختار :user إلغاء طلب التجارة هذا',
        TraderOrderCancelReason::TraderOrderIsCancelled => 'لقد اختار :user إلغاء هذا الطلب',
        TraderOrderCancelReason::NoEligibleCommoditiesAvailable => 'لا يوجد سلع كافيه',
        TraderOrderCancelReason::ExpiredContractSignTime => 'تم إلغاء طلب التجارة من قبل النظام بسبب انتهاء وقت توقيع العقد المحدد بـ :value ساعة.',
        TraderOrderCancelReason::BursamBuyOrderRetriesExceeded => 'تم إلغاء طلب التجارة من قبل النظام بسبب تجاوز عدد المحاولات المحددة.',
    ],
    UnitOwnershipAction::class => [
        UnitOwnershipAction::SellCommodity => 'بيع السلعة',
        UnitOwnershipAction::PurchaseCommodity => 'شراء السلعة',
        UnitOwnershipAction::BorrowerOwnershipTransfer => 'نقل الملكية إلى المقترض',
        UnitOwnershipAction::Cancel => 'إلغاء',
        UnitOwnershipAction::DeliverCommodity => 'تأكيد التسليم',
    ],
    TraderOrderTimeLimitType::class => [
        TraderOrderTimeLimitType::ContractSignTimeLimit => 'توقيع العقد',
        TraderOrderTimeLimitType::DeliveryConfirmationTimeLimit => 'تأكيد الوصول',
    ],

    CompanyLenderClientType::class => [
        CompanyLenderClientType::Business => 'شركات',
        CompanyLenderClientType::Individual => 'فردي',
    ],

    FinancingOrderTypeEnum::class => [
        FinancingOrderTypeEnum::NormalLending => 'تمويل',
        FinancingOrderTypeEnum::SpecialPurposeVehicle => 'مرابحة الاستثمار لغرض خاص',
        FinancingOrderTypeEnum::TimeDeposit => 'وديعة لأجل',
    ],

    'document_type' => [
        'client_wakala' => 'وكالة العميل',
        'transfer_ownership_to_lender' => 'نقل الملكية إلى المقرض',
        'selling_commodity_to_customer' => 'بيع السلعة للعميل',
        'sell_confirmation_document' => 'مستند تأكيد البيع',
        'bursam_bid_certificate' => 'شهادة عرض بورصة',
        'bursam_stb_certificate' => 'شهادة بورصة STB',
        'bursam_otc_certificate' => 'شهادة بورصة OTC',
        'selling_pledge_certificate' => 'شهادة بيع الرهن',
        'voucher_receipt' => 'إيصال القسيمة',
        'zatca_invoice' => 'فاتورة ضريبة القيمة المضافة',
    ],
];
