<?php

namespace App\Actions\Lenders;

use App\Actions\Contracts\Lenders\GetValidCommodityTypes;
use App\Enums\CommodityTypeStatus;
use App\Enums\CompanyMarketType;
use App\Enums\Trader;
use App\Enums\TraderOrderMode;
use App\Models\CommodityType;
use App\Models\Company;

class GetValidCommodityTypesAction implements GetValidCommodityTypes
{
    public function handle(Company $company): array
    {
        $lender = $company->lender;

        if (! $lender || ! $lender->lenderDetail || ! $lender->lenderDetail->allow_preferred_commodity_in_order) {
            return [];
        }

        $tradingMode = $lender->lenderDetail->trading_mode;
        $preferredMarketType = $lender->lenderDetail->preferred_market_type;

        // Build the commodity query
        $query = CommodityType::query()
            ->where('status', CommodityTypeStatus::Active);

        if ($tradingMode->value === TraderOrderMode::Automatic) {
            if ($preferredMarketType->value === CompanyMarketType::Local) {
                $query->where('provider', Trader::Lynk);
            } elseif ($preferredMarketType->value === CompanyMarketType::International) {
                $query->where('provider', Trader::Bursam);
            }
            // 'ANY' will fetch all active commodities
        }
        // 'MANUAL' also fetches all active commodities

        return $query->get()
            ->map(function ($commodity) {
                return [
                    'id' => $commodity->unique_name,
                    'name' => $commodity->name,
                ];
            })->values()->toArray();
    }
}
