<?php

declare(strict_types=1);

use App\Enums\CompanyStatus;
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

];
