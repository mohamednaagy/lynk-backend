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
use RuntimeException;

/**
 * Service for resolving suitable commodity types for trader orders
 *
 * Implements hierarchical fallback strategy:
 * 1. TraderOrder -> 2. FinancingOrder -> 3. Company -> 4. GlobalSettings
 */
final class GetSuitableCommodityTypeService
{
    public function __construct(
        private readonly TraderOrder $traderOrder
    ) {
        Log::withContext([
            'traderOrderId' => $this->traderOrder->id,
        ]);
    }

    /**
     * Resolve the best-fit commodity for the trader order
     *
     * @return CommodityType The resolved commodity type
     *
     * @throws RuntimeException If no suitable commodity found
     */
    public function resolve(): CommodityType
    {
        $commodityType = $this->resolveCommodityTypeWithFallbacks();
        if (is_null($commodityType)) {
            Log::error('No suitable commodity type found');
            throw new \RuntimeException('No suitable commodity type found for the given order');
        }

        return $commodityType;
    }

    /**
     * Resolves a suitable commodity by checking multiple sources in order of importance:
     * - TraderOrder (if a commodity_type_id is already set).
     * - FinancingOrder (if the financing order has a default commodity).
     * - Company preferences (via allowed commodity types).
     * - Global application-wide defaults (based on the trader/provider).
     */
    private function resolveCommodityTypeWithFallbacks(): ?CommodityType
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

    /**
     * Get commodity from trader order if explicitly set
     */
    private function resolveFromTraderOrder(): ?CommodityType
    {
        return $this->traderOrder->commodity_type_id
            ? $this->traderOrder->commodityType
            : null;
    }

    /**
     * Get commodity from financing order with provider validation
     * used when the trader order does not have a specific commodity type set
     */
    private function resolveFromFinancingOrder(): ?CommodityType
    {
        $financingOrder = $this->traderOrder->order;

        return $financingOrder->commodity_type_id
            ? $financingOrder->commodityType()->where('provider', $this->traderOrder->provider)->first()
            : null;
    }

    /**
     * Get active commodity from company preferences
     * Used when the company has assigned active commodities specific to a trader provider, and neither the trader order nor the financing order has a specific commodity type set
     */
    private function resolveFromCompany(): ?CommodityType
    {
        $company = $this->traderOrder->order->company;

        return $company->commodityTypes()
            ->where('provider', $this->traderOrder->provider)
            ->where('status', CommodityTypeStatus::Active)
            ->first();
    }

    /**
     *Resolve from global settings based on trader provider
     * Used when the order, company, and financing order don't provide a commodity type.
     *
     * @throws InvalidArgumentException For unsupported providers.
     */
    private function resolveFromGlobalSettings(): ?CommodityType
    {
        $preferredTrader = $this->traderOrder->provider;

        return match ($preferredTrader) {
            Trader::Bursam => $this->resolveBursamFromGlobalSettings(),
            Trader::Lynk => $this->resolveLynkFromGlobalSettings(),
            default => throw new InvalidArgumentException("Unsupported trader: {$preferredTrader->value}")
        };
    }

    /**
     * Get Default commodity type of bursa with preference ordering and availability filtering
     * This is used when no specific preference when trader provider is Bursam and no commodity type is set in the trader order or financing order or company.
     *
     * @throws \RuntimeException If no eligible commodity is found.
     */
    private function resolveBursamFromGlobalSettings(): ?CommodityType
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
        Log::warning('No active Bursa commodity found in GlobalSettings');

        return null;
    }

    /**
     * Randomly selects an active commodity type for Lynk from the database.
     * This is used when no specific preference when trader provider is Lynk and no commodity type is set in the trader order or financing order or company.
     *
     * @throws \RuntimeException If no active Lynk commodity is found.
     */
    private function resolveLynkFromGlobalSettings(): ?CommodityType
    {
        $commodity = CommodityType::query()
            ->where('provider', Trader::Lynk)
            ->where('status', CommodityTypeStatus::Active)
            ->inRandomOrder()
            ->first();

        if ($commodity) {
            Log::info('Resolved from GlobalSettings (Lynk random selection)', [
                'commodity_type_id' => $commodity->id,
                'commodity_name' => $commodity->name,
            ]);

            return $commodity;
        }
        Log::warning('No active Lynk commodity found in GlobalSettings');

        return null;
    }
}
