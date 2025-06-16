<?php

namespace App\Services;

use App\Enums\CommodityTypeStatus;
use App\Enums\Trader;
use App\Models\CommodityType;
use App\Models\TraderOrder;
use App\Settings\Classes\Areas\InternationalMurabahaSetting;
use Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

final class GetSuitableCommodityTypeService
{
    public function __construct(
        private readonly TraderOrder $traderOrder
    ) {
        Log::withContext([
            'traderOrderId' => $this->traderOrder->id,
        ]);
    }

    public function resolve(): CommodityType
    {
        $commodityType = $this->resolveCommodityTypeWithFallbacks();
        if (empty($commodityType)) {
            Log::error('No suitable commodity type found');
            throw new \RuntimeException('No suitable commodity type found for the given order');
        }

        return $commodityType;
    }

    private function resolveCommodityTypeWithFallbacks(): CommodityType
    {
        if ($commodity = $this->resolveFromTraderOrder()) {
            Log::info('CommodityType resolved directly from TraderOrder', [
                'commodity_type_id' => $commodity->id,
            ]);

            return $commodity;
        }

        if ($commodity = $this->resolveFromFinancingOrder()) {
            Log::info('CommodityType resolved directly from FinancingOrder', [
                'commodity_type_id' => $commodity->id,
            ]);

            return $commodity;
        }

        if ($commodity = $this->resolveFromCompany()) {
            Log::info('CommodityType resolved directly from Company', [
                'commodity_type_id' => $commodity->id,
            ]);

            return $commodity;
        }

        Log::warning('Falling back to GlobalSettings (TraderOrder, FinancingOrder, Company all returned null)');

        return $this->resolveFromGlobalSettings();
    }

    private function resolveFromTraderOrder(): ?CommodityType
    {
        return $this->traderOrder->commodity_type_id
            ? $this->traderOrder->commodityType
            : null;
    }

    private function resolveFromFinancingOrder(): ?CommodityType
    {
        $financingOrder = $this->traderOrder->order;

        return $financingOrder->commodity_type_id
            ? $financingOrder->commodityType()->where('provider', $this->traderOrder->provider)->first()
            : null;
    }

    private function resolveFromCompany(): ?CommodityType
    {
        $company = $this->traderOrder->order->company;

        return $company->commodityTypes()
            ->where('provider', $this->traderOrder->provider)
            ->where('status', CommodityTypeStatus::Active)
            ->first();
    }

    private function resolveFromGlobalSettings(): CommodityType
    {
        $preferredTrader = $this->traderOrder->provider;

        return match ($preferredTrader) {
            Trader::Bursam => $this->resolveBursamFromGlobalSettings(),
            Trader::Lynk => $this->resolveLynkFromGlobalSettings(),
            default => throw new InvalidArgumentException("Unsupported trader: {$preferredTrader->value}")
        };
    }

    private function resolveBursamFromGlobalSettings(): CommodityType
    {
        $defaultId = app(InternationalMurabahaSetting::class)->bursam_default_preferred_commodity_type;
        $query = CommodityType::query()->where('status', CommodityTypeStatus::Active)->where('provider', Trader::Bursam);

        if ($defaultId) {
            $query = $query->orderByRaw(
                'CASE WHEN id = ? THEN 0 ELSE 1 END',
                [$defaultId]
            );
        }

        $unavailableProductCodes = (array) Cache::get('bursam_unavailable_product_codes', []);
        Log::info('Unavailable product codes from cache', [
            'traderOrderId' => $this->traderOrder->id,
            'unavailable_product_codes' => $unavailableProductCodes,
            'count' => count($unavailableProductCodes),
        ]);

        if (! empty($unavailableProductCodes)) {
            $query->whereNotIn('unique_name', $unavailableProductCodes);
        }

        $commodity = $query->first();

        if ($commodity) {
            Log::info('CommodityType resolved from GlobalSettings', [
                'commodity_type_id' => $commodity->id,
            ]);

            return $commodity;
        }
        Log::error("GlobalSettings failed to resolve commodity type for Bursam with ID: {$defaultId}");
        throw new \RuntimeException('Failed to resolve commodity type for Bursam from global settings');
    }

    private function resolveLynkFromGlobalSettings(): CommodityType
    {
        $commodity = CommodityType::query()
            ->where('provider', Trader::Lynk)
            ->where('status', CommodityTypeStatus::Active)
            ->inRandomOrder()
            ->first();

        if (! $commodity) {
            Log::error('GlobalSettings failed to resolve commodity type for Lynk (no active records found)');
            throw new \RuntimeException('Failed to resolve commodity type for Lynk from global settings');
        }

        Log::info('Resolved from GlobalSettings (Lynk random selection)', [
            'commodity_type_id' => $commodity->id,
            'commodity_name' => $commodity->name,
        ]);

        return $commodity;
    }
}
