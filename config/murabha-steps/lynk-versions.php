<?php

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\MurabhaStep;

return [
    'v1' => [
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
            FinancingOrderHistory::PendingDelivery => null,

        ],
        MurabhaStep::CommoditySoldToCustomer => [
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument => null,
        ],
        MurabhaStep::CustomerDeliveryConfirmation => [
            FinancingOrderHistory::DeliveryConfirmed => null,
            FinancingOrderHistory::DeliveryCancelled => null,
        ],
        MurabhaStep::MurabahaSaleCompleted => [
            FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument => null,
            FinancingOrderHistory::CreateLynkSalePledgeCertificate => null,
            FinancingOrderHistory::MurabahaSaleCompleted => null,
        ],

    ],
];
