<?php

namespace App\Actions\Lenders;

use App\Actions\Contracts\Lenders\GetValidCommodityTypes;
use App\Enums\CommodityTypeStatus;
use App\Enums\CompanyMarketType;
use App\Enums\Trader;
use App\Enums\TraderOrderMode;
use App\Models\CommodityType;
use App\Models\Company;
use Illuminate\Support\Facades\Log;

class GetValidCommodityTypesAction implements GetValidCommodityTypes
{
    public function handle(Company $company): array
    {
        $lender = $company->lender;

        if (!$this->isPreferredCommoditySelectionAllowed($lender)) {
            Log::info('Lender does not allow selecting preferred commodity types in orders.', [
                'company_id' => $company->id,
                'lender_id' => $lender?->id,
                'allow_preferred_commodity_in_order' => $lender?->lenderDetail?->allow_preferred_commodity_in_order ?? false
            ]);

            return [];
        }

        $lenderDetail = $lender->lenderDetail;

        $query = CommodityType::query()->where('status', CommodityTypeStatus::Active);

        if ($lenderDetail->trading_mode->value === TraderOrderMode::Automatic) {
            match ($lenderDetail->preferred_market_type->value) {
                CompanyMarketType::Local => $query->where('provider', Trader::Lynk),
                CompanyMarketType::International => $query->where('provider', Trader::Bursam),
                default => null // 'ANY' case: no additional filtering
            };
        }
        // For 'MANUAL' mode: fetch all active commodities without filtering

        return $query->get()
            ->map(fn($commodity) => [
                'id' => $commodity->unique_name,
                'name' => $commodity->name,
            ])->values()->toArray();
    }

    private function isPreferredCommoditySelectionAllowed($lender): bool
    {
        return $lender && $lender->lenderDetail && ($lender->lenderDetail->allow_preferred_commodity_in_order ?? false);
    }
}
