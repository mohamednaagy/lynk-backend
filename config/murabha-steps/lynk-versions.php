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
        ],
        MurabhaStep::CommoditySoldToCustomer => [
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument => null,
        ],
        MurabhaStep::CustomerDeliveryConfirmation => [
        ],
        MurabhaStep::MurabahaSaleCompleted => [
            // @TODO_localmarket need to double check if we really need this step or not
            FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument => null,
            FinancingOrderHistory::AttachWarrantAmendmentExceptWarrantNoDocument => [
                // need to change collection to sell pledge document
                'collection' => TraderOrderMediaCollection::WarrantAmendmentExceptWarrantNo,
                'file' => 'document',
            ],
            FinancingOrderHistory::MurabahaSaleCompleted => null,
            // need to add attach sell confirmation document
        ],
    ],
];
