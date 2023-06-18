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

    EdaatInvoiceStatus::class => [
        EdaatInvoiceStatus::Pending => 'Pending',
        EdaatInvoiceStatus::Paid => 'Paid',
        EdaatInvoiceStatus::Expired => 'Expired',
    ],

    BursamMurabhaStep::class => [
        BursamMurabhaStep::TraderOrderCreated => 'Trader Order Created',
        BursamMurabhaStep::PurchasingCommodity => 'Purchasing Commodity',
        BursamMurabhaStep::ContractSigned => 'Contract Signed',
        BursamMurabhaStep::CommoditySoldToCustomer => 'Commodity Sold To Customer',
        BursamMurabhaStep::TransferOwnershipToLender => 'Transfer Ownership To Lender',
        BursamMurabhaStep::ClientWakala => 'Client Wakala',
        BursamMurabhaStep::MurabhaOfferIssued => 'Murabha Offer Issued',
        BursamMurabhaStep::MurabahaSaleCompleted => 'Murabaha Sale Completed',
    ],

    DmccMurabhaStep::class => [
        DmccMurabhaStep::TraderOrderCreated => 'Trader Order Created',
        DmccMurabhaStep::PurchasingCommodity => 'Purchasing Commodity',
        DmccMurabhaStep::ContractSigned => 'Contract Signed',
        DmccMurabhaStep::CommoditySoldToCustomer => 'Commodity Sold To Customer',
        DmccMurabhaStep::ClientWakala => 'Client Wakala',
        DmccMurabhaStep::MurabhaOfferIssued => 'Murabha Offer Issued',
        DmccMurabhaStep::MurabahaSaleCompleted => 'Murabaha Sale Completed',
    ],
];
