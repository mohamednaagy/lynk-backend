<?php

namespace App\Transformers\HelperTransformer;

use App\Enums\FinancingOrderHistory;

trait TransformerHelperTrait
{
    public function getHistoriesUiSteps($provider, $version)
    {

        $historyKeys = $provider.'.'.$version;

        return match ($historyKeys) {
            'bursam.v1','fake.v1','dmcc.v1' => [
                FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
                FinancingOrderHistory::ContractSigned,
                FinancingOrderHistory::ClientWakalaAccepted,
                FinancingOrderHistory::CreateSellingCommodityToCustomerDocument,
                FinancingOrderHistory::IssueMurabahaOffer,
                FinancingOrderHistory::MurabahaSaleCompleted,
            ],
            'bursam.v2' => [
                FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
                FinancingOrderHistory::ClientWakalaAccepted,
                FinancingOrderHistory::ContractSigned,
                FinancingOrderHistory::CreateSellingCommodityToCustomerDocument,
                FinancingOrderHistory::MurabahaSaleCompleted,
            ]
        };
    }
}
