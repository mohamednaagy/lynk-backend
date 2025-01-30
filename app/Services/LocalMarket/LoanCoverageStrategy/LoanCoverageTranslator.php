<?php

namespace App\Services\LocalMarket\LoanCoverageStrategy;

use app\models\LocalMarketInventory;

class LoanCoverageTranslator
{
    public static function translate(LocalMarketInventory $inventory, int $numberOfUnits, int $amount): array
    {
        return [
            'id' => $inventory->id,
            'commodity_type_id' => $inventory->commodity_type_id,
            'numberOfUnits' => $numberOfUnits,
            'amount' => $amount,
        ];
    }
}
