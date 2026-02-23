<?php

use App\Enums\ContractSignedType;
use App\Enums\FinancingOrderHistory;
use App\Enums\MurabhaStep;

return [
    'v1' => [
        ContractSignedType::Sell => [
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument => [
                'step' => MurabhaStep::PurchasingCommodity,
                'start_history' => FinancingOrderHistory::GetTtiId,
                'end_history' => FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
            ],
            FinancingOrderHistory::ContractSigned => [
                'step' => MurabhaStep::ContractSigned,
                'start_history' => FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
                'end_history' => FinancingOrderHistory::ContractSigned,
            ],
            FinancingOrderHistory::ClientWakalaAccepted => [
                'step' => MurabhaStep::ClientWakala,
                'start_history' => FinancingOrderHistory::ContractSigned,
                'end_history' => FinancingOrderHistory::ClientWakalaAccepted,
            ],
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument => [
                'step' => MurabhaStep::CommoditySoldToCustomer,
                'start_history' => FinancingOrderHistory::ClientWakalaAccepted,
                'end_history' => FinancingOrderHistory::CreateSellingCommodityToCustomerDocument,
            ],
            FinancingOrderHistory::AttachMpoDocument => [
                'step' => MurabhaStep::MurabhaOfferIssued,
                'start_history' => FinancingOrderHistory::CreateSellingCommodityToCustomerDocument,
                'end_history' => FinancingOrderHistory::AttachMpoDocument,
            ],
            FinancingOrderHistory::MurabahaSaleCompleted => [
                'step' => MurabhaStep::MurabahaSaleCompleted,
                'start_history' => FinancingOrderHistory::AttachMpoDocument,
                'end_history' => FinancingOrderHistory::MurabahaSaleCompleted,
            ],
        ],
    ],
    'v2' => [
        ContractSignedType::Sell => [
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument => [
                'step' => MurabhaStep::PurchasingCommodity,
                'start_history' => FinancingOrderHistory::GetTtiId,
                'end_history' => FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
            ],
            FinancingOrderHistory::ContractSigned => [
                'step' => MurabhaStep::ContractSigned,
                'start_history' => FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
                'end_history' => FinancingOrderHistory::ContractSigned,
            ],
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument => [
                'step' => MurabhaStep::CommoditySoldToCustomer,
                'start_history' => FinancingOrderHistory::ContractSigned,
                'end_history' => FinancingOrderHistory::CreateSellingCommodityToCustomerDocument,
            ],
            FinancingOrderHistory::ClientWakalaAccepted => [
                'step' => MurabhaStep::ClientWakala,
                'start_history' => FinancingOrderHistory::CreateSellingCommodityToCustomerDocument,
                'end_history' => FinancingOrderHistory::ClientWakalaAccepted,
            ],
            FinancingOrderHistory::MurabahaSaleCompleted => [
                'step' => MurabhaStep::MurabahaSaleCompleted,
                'start_history' => FinancingOrderHistory::ClientWakalaAccepted,
                'end_history' => FinancingOrderHistory::MurabahaSaleCompleted,
            ],
        ],
    ],
];
