<?php

namespace App\Services;

use App\Enums\CommodityTypeStatus;
use App\Enums\Trader;
use App\Models\CommodityType;
use App\Models\TraderOrder;
use App\Settings\Classes\Areas\InternationalMurabahaSetting;
use Cache;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

/**
 * Service for resolving suitable commodities types for trader orders
 *
 * Implements hierarchical fallback strategy:
 * 1. TraderOrder -> 2. FinancingOrder -> 3. Company -> 4. GlobalSettings
 */
final class GetSuitableCommoditiesTypeService
{
    private $isForced = false;

    private $logChannel = '';

    public function __construct(
        private readonly TraderOrder $traderOrder
    ) {
        $this->logChannel = $this->traderOrder->provider === Trader::Bursam ? 'bursam' : 'local_market';
        Log::withContext([
            'traderOrderId' => $this->traderOrder->id,
            'provider' => $this->traderOrder->provider,
        ]);
    }

    /**
     * Resolve the best-fit list of commodities for the trader order
     *
     * @return CommodityType The resolved commodity type
     *
     * @throws RuntimeException If no suitable commodity found
     */
    public function resolve(): array
    {
        $commodityTypes = $this->resolveCommoditiesTypeWithFallbacks();

        if ($commodityTypes->isEmpty()) {
            Log::channel($this->logChannel)->error('No suitable commodity type found', [
                'traderOrderId' => $this->traderOrder->id,
                'provider' => $this->traderOrder->provider,
            ]);
            throw new \RuntimeException('No suitable commodity type found for the given order');
        }

        $identifier_key = $this->traderOrder->provider === Trader::Bursam ? 'unique_name' : 'id';

        return [
            'commodities_type_id' => $commodityTypes->pluck($identifier_key)->toArray(),
            'force_commodity_type' => $this->isForced,
        ];
    }

    /**
     * Resolves a suitable list of commodities by checking multiple sources in order of importance:
     * - TraderOrder (if a commodity_type_id is already set).
     * - FinancingOrder (if the financing order has a default commodity).
     * - Company preferences (via allowed commodity types).
     * - Global application-wide defaults (based on the trader/provider).
     */
    private function resolveCommoditiesTypeWithFallbacks(): ?Collection
    {
        if ($commodities = $this->resolveFromTraderOrder()) {
            $this->isForced = true;
            Log::channel($this->logChannel)->info('CommodityType resolved directly from TraderOrder', [
                'commodity_type_id' => $commodities->pluck('id')->toArray(),
                'force_commodity_type' => $this->isForced,
            ]);

            return $commodities;
        }

        if ($commodities = $this->resolveFromFinancingOrder()) {
            $this->isForced = true;
            Log::channel($this->logChannel)->info('CommodityType resolved directly from FinancingOrder', [
                'commodity_type_id' => $commodities->pluck('id')->toArray(),
                'force_commodity_type' => $this->isForced,
            ]);

            return $commodities;
        }

        if ($commodities = $this->resolveFromCompany()) {
            $this->isForced = $this->traderOrder->provider == Trader::Bursam ? true : $this->traderOrder->order->company->lender->lenderDetail->force_preferred_commodity_type;
            Log::channel($this->logChannel)->info('CommodityType resolved directly from Company', [
                'commodity_type_id' => $commodities->pluck('id')->toArray(),
                'force_commodity_type' => $this->isForced,
            ]);

            return $commodities;
        }

        Log::channel($this->logChannel)->warning('Falling back to GlobalSettings', [
            'reason' => 'TraderOrder, FinancingOrder, and Company all returned null',
        ]);

        return $this->resolveFromGlobalSettings();
    }

    /**
     * Get commodity from trader order if explicitly set
     */
    private function resolveFromTraderOrder(): ?collection
    {
        return $this->traderOrder->commodity_type_id
            ? $this->traderOrder->commodityType()->where('provider', $this->traderOrder->provider)->where('status', CommodityTypeStatus::Active)->get()
            : null;
    }

    /**
     * Get commodity from financing order with provider validation
     * used when the trader order does not have a specific commodity type set
     */
    private function resolveFromFinancingOrder(): ?collection
    {
        $financingOrder = $this->traderOrder->order;

        return $financingOrder->commodity_type_id
            ? $financingOrder->commodityType()->where('provider', $this->traderOrder->provider)->where('status', CommodityTypeStatus::Active)->get()
            : null;
    }

    /**
     * Get active commodities from company preferences
     * Used when the company has assigned active commodities specific to a trader provider, and neither the trader order nor the financing order has a specific commodity type set
     */
    private function resolveFromCompany(): ?collection
    {
        $company = $this->traderOrder->order->company;
        $commodities = $company->commodityTypes()
            ->where('provider', $this->traderOrder->provider)
            ->where('status', CommodityTypeStatus::Active)
            ->get();

        if ($commodities->isNotEmpty()) {
            return $commodities;
        }

        return null;
    }

    /**
     *Resolve from global settings based on trader provider
     * Used when the order, company, and financing order don't provide a commodity type.
     *
     * @throws InvalidArgumentException For unsupported providers.
     */
    private function resolveFromGlobalSettings(): ?collection
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
    private function resolveBursamFromGlobalSettings(): ?collection
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
        Log::channel($this->logChannel)->info('Unavailable product codes from cache', [
            'traderOrderId' => $this->traderOrder->id,
            'unavailable_product_codes' => $unavailableProductCodes,
            'count' => count($unavailableProductCodes),
        ]);

        if (! empty($unavailableProductCodes)) {
            $query->whereNotIn('unique_name', $unavailableProductCodes);
        }

        $commodities = $query->get();

        if ($commodities->isNotEmpty()) {
            Log::channel($this->logChannel)->info('CommodityType resolved from GlobalSettings', [
                'commodities_type_id' => $commodities->pluck('id')->toArray(),
                'force_commodity_type' => $this->isForced,

            ]);

            return $commodities;
        }
        Log::channel($this->logChannel)->warning('No active Bursa commodity found in GlobalSettings');

        return null;
    }

    /**
     * Randomly selects an active commodity type for Lynk from the database.
     * This is used when no specific preference when trader provider is Lynk and no commodity type is set in the trader order or financing order or company.
     *
     * @throws \RuntimeException If no active Lynk commodity is found.
     */
    private function resolveLynkFromGlobalSettings(): ?Collection
    {
        $commodities = CommodityType::query()
            ->where('provider', Trader::Lynk)
            ->where('status', CommodityTypeStatus::Active)
            ->inRandomOrder()
            ->get();

        if ($commodities->isNotEmpty()) {
            Log::channel($this->logChannel)->info('Resolved from GlobalSettings (Lynk random selection)', [
                'commodities_type_id' => $commodities->pluck('id')->toArray(),
                'force_commodity_type' => $this->isForced,
            ]);

            return $commodities;
        }
        Log::channel($this->logChannel)->warning('No active Lynk commodity found in GlobalSettings');

        return null;
    }
}
