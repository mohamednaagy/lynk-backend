<?php

namespace App\Services;

use App\Enums\CommodityTypeStatus;
use App\Enums\Trader;
use App\Models\CommodityType;
use App\Models\TraderOrder;
use App\Settings\Classes\InternationalMurabahaSetting;
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
final class GetSuitableCommodityTypesService
{
    private $logChannel = '';

    public function __construct(
        private readonly TraderOrder $traderOrder
    ) {
        $this->logChannel = $this->traderOrder->provider === Trader::Bursam ? LOG_CHANNEL_BURSAM : LOG_CHANNEL_LOCAL_MARKET;
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

        $commodityTypes = $this->resolveCommodityTypesWithFallbacks();
        if ($commodityTypes->isEmpty()) {
            Log::channel($this->logChannel)->error(formatLogTitle('No suitable commodity type found', $this->traderOrder), [
                'financingOrderId' => $this->traderOrder->financing_order_id,
                'traderOrderId' => $this->traderOrder->id,
                'provider' => $this->traderOrder->provider,
            ]);
            throw new \RuntimeException('No suitable commodity type found for the given order');
        }

        return [
            'commodity_types_id' => $commodityTypes->pluck('unique_name')->toArray(),
        ];
    }

    /**
     * Resolves a suitable list of commodities by checking multiple sources in order of importance:
     * - TraderOrder (if a commodity_type_id is already set).
     * - FinancingOrder (if the financing order has a default commodity).
     * - Company preferences (via allowed commodity types).
     * - Global application-wide defaults (based on the trader/provider).
     */
    private function resolveCommodityTypesWithFallbacks(): ?Collection
    {

        if ($this->traderOrder->hasAnyCommodityType()) {
            $commodities = $this->resolveFromGlobalSettings();
            Log::channel($this->logChannel)->info(formatLogTitle('CommodityType resolved directly from TraderOrder (ANY)', $this->traderOrder), [
                'financingOrderId' => $this->traderOrder->financing_order_id,
                'traderOrderId' => $this->traderOrder->id,
                'commodity_type_id' => $commodities->pluck('unique_name')->toArray(),
            ]);

            return $commodities;
        }

        if ($commodities = $this->resolveFromTraderOrder()) {
            if ($commodities->isNotEmpty()) {
                Log::channel($this->logChannel)->info(formatLogTitle('CommodityType resolved directly from TraderOrder', $this->traderOrder), [
                    'financingOrderId' => $this->traderOrder->financing_order_id,
                    'traderOrderId' => $this->traderOrder->id,
                    'commodity_type_id' => $commodities->pluck('id')->toArray(),
                ]);

                return $commodities;
            }

        }

        if ($commodities = $this->resolveFromFinancingOrder()) {
            if ($commodities->isNotEmpty()) {
                Log::channel($this->logChannel)->info(formatLogTitle('CommodityType resolved directly from FinancingOrder', $this->traderOrder), [
                    'financingOrderId' => $this->traderOrder->financing_order_id,
                    'traderOrderId' => $this->traderOrder->id,
                    'commodity_type_id' => $commodities->pluck('id')->toArray(),
                ]);

                return $commodities;
            }

        }

        if ($commodities = $this->resolveFromCompany()) {
            if ($commodities->isNotEmpty()) {
                Log::channel($this->logChannel)->info(formatLogTitle('CommodityType resolved directly from Company', $this->traderOrder), [
                    'financingOrderId' => $this->traderOrder->financing_order_id,
                    'traderOrderId' => $this->traderOrder->id,
                    'commodity_type_id' => $commodities->pluck('id')->toArray(),
                ]);

                return $commodities;
            }
        }

        Log::channel($this->logChannel)->warning(formatLogTitle('Falling back to GlobalSettings', $this->traderOrder), [
            'financingOrderId' => $this->traderOrder->financing_order_id,
            'traderOrderId' => $this->traderOrder->id,
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
        Log::channel($this->logChannel)->info(formatLogTitle('Unavailable product codes from cache', $this->traderOrder), [
            'financingOrderId' => $this->traderOrder->financing_order_id,
            'traderOrderId' => $this->traderOrder->id,
            'unavailable_product_codes' => $unavailableProductCodes,
            'count' => count($unavailableProductCodes),
        ]);

        if (! empty($unavailableProductCodes)) {
            $query->whereNotIn('unique_name', $unavailableProductCodes);
        }

        $commodities = $query->get();

        if ($commodities->isNotEmpty()) {
            Log::channel($this->logChannel)->info(formatLogTitle('CommodityType resolved from GlobalSettings', $this->traderOrder), [
                'financingOrderId' => $this->traderOrder->financing_order_id,
                'traderOrderId' => $this->traderOrder->id,
                'commodity_types_id' => $commodities->pluck('id')->toArray(),
            ]);

            return $commodities;
        }
        Log::channel($this->logChannel)->warning(formatLogTitle('No active Bursa commodity found in GlobalSettings', $this->traderOrder));

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
            Log::channel($this->logChannel)->info(formatLogTitle('Resolved from GlobalSettings (Lynk random selection)', $this->traderOrder), [
                'financingOrderId' => $this->traderOrder->financing_order_id,
                'traderOrderId' => $this->traderOrder->id,
                'commodity_types_id' => $commodities->pluck('id')->toArray(),
            ]);

            return $commodities;
        }
        Log::channel($this->logChannel)->warning(formatLogTitle('No active Lynk commodity found in GlobalSettings', $this->traderOrder));

        return null;
    }
}
