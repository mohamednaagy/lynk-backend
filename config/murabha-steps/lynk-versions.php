<?php

use App\Enums\ContractSignedType;
use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\MurabhaStep;

return [
    'v1' => [
        ContractSignedType::Sell => [
            MurabhaStep::TraderOrderCreated => [
                FinancingOrderHistory::GetTtiId => null,
            ],
            MurabhaStep::PurchasingCommodity => [
                FinancingOrderHistory::GetTtiHoldingCertificateDocument => null,
                FinancingOrderHistory::AttachTtiHoldingCertificateDocument => [
                    'collection' => TraderOrderMediaCollection::TtiHoldingCertificate,
                    'file' => 'original_holding_certificate',
                ],
                FinancingOrderHistory::CreateTransferOwnershipToLenderDocument => null,
            ],
            MurabhaStep::ContractSigned => [
                FinancingOrderHistory::PendingDelivery => null,
                FinancingOrderHistory::ContractSigned => null,
            ],
            MurabhaStep::CommoditySoldToCustomer => [
                FinancingOrderHistory::CreateSellingCommodityToCustomerDocument => null,
            ],
            MurabhaStep::MurabahaSaleCompleted => [
                FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument => null,
                FinancingOrderHistory::CreateLynkSalePledgeCertificate => null,
                FinancingOrderHistory::MurabahaSaleCompleted => null,
            ],
        ],
        ContractSignedType::Delivery => [
            MurabhaStep::TraderOrderCreated => [
                FinancingOrderHistory::GetTtiId => null,
            ],
            MurabhaStep::PurchasingCommodity => [
                FinancingOrderHistory::GetTtiHoldingCertificateDocument => null,
                FinancingOrderHistory::AttachTtiHoldingCertificateDocument => [
                    'collection' => TraderOrderMediaCollection::TtiHoldingCertificate,
                    'file' => 'original_holding_certificate',
                ],
                FinancingOrderHistory::CreateTransferOwnershipToLenderDocument => null,
            ],
            MurabhaStep::ContractSigned => [
                FinancingOrderHistory::PendingDelivery => null,
                FinancingOrderHistory::ContractSigned => null,
            ],
            MurabhaStep::CommoditySoldToCustomer => [
                FinancingOrderHistory::CreateSellingCommodityToCustomerDocument => null,
            ],
            MurabhaStep::CustomerDeliveryConfirmation => [
                FinancingOrderHistory::DeliveryCancelled => null,
                FinancingOrderHistory::DeliveryConfirmed => null,
            ],
            MurabhaStep::MurabahaSaleCompleted => [
                FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument => null,
                FinancingOrderHistory::CreateLynkSalePledgeCertificate => null,
                FinancingOrderHistory::MurabahaSaleCompleted => null,
            ],
        ],
    ],
    'v2' => [
        ContractSignedType::Sell => [
            MurabhaStep::TraderOrderCreated => [
                FinancingOrderHistory::GetTtiId => null,
            ],
            MurabhaStep::PurchasingCommodity => [
                FinancingOrderHistory::GetTtiHoldingCertificateDocument => null,
                FinancingOrderHistory::AttachTtiHoldingCertificateDocument => [
                    'collection' => TraderOrderMediaCollection::TtiHoldingCertificate,
                    'file' => 'original_holding_certificate',
                ],
                FinancingOrderHistory::CreateTransferOwnershipToLenderDocument => null,
            ],
            MurabhaStep::ContractSigned => [
                FinancingOrderHistory::ContractSigned => null,
            ],
            MurabhaStep::CommoditySoldToCustomer => [
                FinancingOrderHistory::CreateSellingCommodityToCustomerDocument => null,
            ],
            MurabhaStep::ClientWakala => [
                FinancingOrderHistory::WaitingClientWakala => null,
                FinancingOrderHistory::ClientWakalaAccepted => null,
            ],
            MurabhaStep::MurabahaSaleCompleted => [
                FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument => null,
                FinancingOrderHistory::CreateLynkSalePledgeCertificate => null,
                FinancingOrderHistory::MurabahaSaleCompleted => null,
            ],
        ],
        ContractSignedType::Delivery => [
            MurabhaStep::TraderOrderCreated => [
                FinancingOrderHistory::GetTtiId => null,
            ],
            MurabhaStep::PurchasingCommodity => [
                FinancingOrderHistory::GetTtiHoldingCertificateDocument => null,
                FinancingOrderHistory::AttachTtiHoldingCertificateDocument => [
                    'collection' => TraderOrderMediaCollection::TtiHoldingCertificate,
                    'file' => 'original_holding_certificate',
                ],
                FinancingOrderHistory::CreateTransferOwnershipToLenderDocument => null,
            ],
            MurabhaStep::ContractSigned => [
                FinancingOrderHistory::ContractSigned => null,
            ],
            MurabhaStep::CommoditySoldToCustomer => [
                FinancingOrderHistory::CreateSellingCommodityToCustomerDocument => null,
            ],
            MurabhaStep::ClientWakala => [
                FinancingOrderHistory::WaitingClientWakala => null,
                FinancingOrderHistory::DeliveryConfirmed => null,
            ],
            MurabhaStep::MurabahaSaleCompleted => [
                FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument => null,
                FinancingOrderHistory::CreateLynkSalePledgeCertificate => null,
                FinancingOrderHistory::MurabahaSaleCompleted => null,
            ],
        ],
    ],
];
