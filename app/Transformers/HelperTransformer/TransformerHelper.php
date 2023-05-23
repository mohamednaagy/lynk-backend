<?php

namespace App\Transformers\HelperTransformer;

use App\Enums\FinancingOrderHistory;

trait TransformerHelper
{
    public function getHistoriesUiSteps($provider, $version)
    {

        $historyKeys = $provider.'.'.$version;

        return match ($historyKeys) {
            'dmcc.v1' => [
                FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
                FinancingOrderHistory::ContractSigned,
                FinancingOrderHistory::ClientWakalaAccepted,
                FinancingOrderHistory::CreateSellingCommodityToCustomerDocument,
                FinancingOrderHistory::MurabahaSaleCompleted,
            ],
            'fake.v1' => [
                FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
                FinancingOrderHistory::ContractSigned,
                FinancingOrderHistory::ClientWakalaAccepted,
                FinancingOrderHistory::CreateSellingCommodityToCustomerDocument,
                FinancingOrderHistory::MurabahaSaleCompleted,
            ],
            'bursam.v2' => [
                FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
                FinancingOrderHistory::ClientWakalaAccepted,
                FinancingOrderHistory::ContractSigned,
                FinancingOrderHistory::CreateSellingCommodityToCustomerDocument,
                FinancingOrderHistory::IssueMurabahaOffer,
                FinancingOrderHistory::MurabahaSaleCompleted,
            ]
        };
    }
}
