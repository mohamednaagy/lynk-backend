<?php

declare(strict_types=1);

use App\Enums\BursamProductCode;
use App\Enums\CommodityTypeStatus;
use App\Enums\CommoitySupplierMarketType;
use App\Enums\CommoitySupplierStatus;
use App\Enums\CompanyStatus;
use App\Enums\EdaatInvoiceStatus;
use App\Enums\EnquiryStatus;
use App\Enums\FinancingOrderStatus;
use App\Enums\MurabhaStep;
use App\Enums\TraderOrderNoRefundReason;
use App\Enums\TraderOrderRefundReason;
use App\Enums\WalletNotificationType;

return [
    CompanyStatus::class => [
        CompanyStatus::Pending => 'Pending',
        CompanyStatus::UnderReview => 'Under review',
        CompanyStatus::Approved => 'Approved',
        CompanyStatus::Rejected => 'Rejected',
    ],

    FinancingOrderStatus::class => [
        FinancingOrderStatus::PendingApproval => 'Pending Approval',
        FinancingOrderStatus::Approved => 'Approved',
        FinancingOrderStatus::Cancelled => 'Cancelled',
        FinancingOrderStatus::Completed => 'Completed',
        FinancingOrderStatus::Rejected => 'Rejected',
        FinancingOrderStatus::InProgress => 'In Progress',
        //        FinancingOrderStatus::CommodityPurchased => 'Commodity Purchased',
        //        FinancingOrderStatus::CommoditySoldToCustomer => 'Commodity Sold To Customer',
        //        FinancingOrderStatus::MurabhaOfferIssued => 'Murabha Offer Issued',
        //        FinancingOrderStatus::MurabahaSaleCompleted => 'Murabaha Sale Completed',
        //        FinancingOrderStatus::ContractSigned => 'Contract Signed',
        //        FinancingOrderStatus::WaitingClientWakala => 'Waiting Client Wakala',
        //        FinancingOrderStatus::WaitingPurchasingCommodity => 'Waiting Purchasing Commodity',
        //        FinancingOrderStatus::ClientWakalaCompleted => 'Client Wakala Completed',
        //        FinancingOrderStatus::RespondedToPtp => 'Responded To Ptp',
        //        FinancingOrderStatus::PtpDocumentRetrieved => 'Ptp Document Retrieved',
        FinancingOrderStatus::PendingCancellation => 'Pending Cancellation',
    ],

    EnquiryStatus::class => [
        EnquiryStatus::UnderReview => 'Under Review',
        EnquiryStatus::Resolved => 'Resolved',
        EnquiryStatus::Closed => 'Closed',
    ],

    CommoitySupplierStatus::class => [
        CommoitySupplierStatus::Active => 'Active',
        CommoitySupplierStatus::Inactive => 'Inactive',
    ],
    CommoitySupplierMarketType::class => [
        CommoitySupplierMarketType::Local => 'Local',
        //        CommoitySupplierMarketType::International => 'International',
    ],

    EdaatInvoiceStatus::class => [
        EdaatInvoiceStatus::Pending => 'Pending',
        EdaatInvoiceStatus::Paid => 'Paid',
        EdaatInvoiceStatus::Expired => 'Expired',
    ],

    MurabhaStep::class => [
        MurabhaStep::TraderOrderCreated => 'Trader Order Created',
        MurabhaStep::PurchasingCommodity => 'Purchasing Commodity',
        MurabhaStep::ContractSigned => 'Contract Signed',
        MurabhaStep::CommoditySoldToCustomer => 'Commodity Sold To Customer',
        MurabhaStep::TransferOwnershipToLender => 'Transfer Ownership To Lender',
        MurabhaStep::ClientWakala => 'Client Wakala',
        MurabhaStep::MurabhaOfferIssued => 'Murabha Offer Issued',
        MurabhaStep::MurabahaSaleCompleted => 'Murabaha Sale Completed',
    ],

    BursamProductCode::class => [
        BursamProductCode::CrudePalmOil => 'Crude Palm Oil',
        BursamProductCode::PlasticResinA => 'Plastic Resin A',
        BursamProductCode::PlasticResinADev => 'Plastic Resin A',
        BursamProductCode::PlasticResinB => 'Plastic Resin B',
        BursamProductCode::PlasticResinBDev => 'Plastic Resin B',
        BursamProductCode::PlumbumLead => 'Lead',
        BursamProductCode::PlumbumLeadDev => 'Plumbum Lead',
        BursamProductCode::RbdPalmOlein => 'Olein Oil',
        BursamProductCode::TimberHardwood => 'Timber Hardwood',
        BursamProductCode::TimberSoftwood => 'Timber Softwood',
    ],
    WalletNotificationType::class => [
        WalletNotificationType::ORDER_COUNT => 'Order Available Threshold',
        WalletNotificationType::WALLET_BALANCE => 'Amount Available Threshold',
    ],
    TraderOrderRefundReason::class => [
        TraderOrderRefundReason::WITHIN_24_HOUR => 'Refunded because cancelled before 24 hours of :base_tr',
        TraderOrderRefundReason::WITHIN_72_HOUR => 'Refunded because cancelled before 72 hours of :base_tr',
    ],
    TraderOrderNoRefundReason::class => [
        TraderOrderNoRefundReason::AFTER_24_HOUR => 'Not refunded because cancelled after 24 hours of :base_tr',
        TraderOrderNoRefundReason::AFTER_72_HOUR => 'Not refunded because cancelled after 72 hours of :base_tr',
    ],

    CommodityTypeStatus::class => [
        CommodityTypeStatus::Active => 'Active',
        CommodityTypeStatus::Inactive => 'Inactive',
    ],

];
