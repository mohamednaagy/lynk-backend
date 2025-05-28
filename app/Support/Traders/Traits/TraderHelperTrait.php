<?php

namespace App\Support\Traders\Traits;

use App\Enums\TraderOrderMode;
use App\Enums\TraderOrderStatus;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\TraderProduct;
use App\Settings\Classes\Areas\InternationalMurabahaSetting;
use App\Support\DataTransferObjects\CommodityProductDto;
use App\Support\DataTransferObjects\LynkCommodityProductDto;
use App\Support\PdfGenerator\PdfGenerator;
use App\Support\Traders\TraderManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

trait TraderHelperTrait
{
    public function createStepHistories(array $data, TraderOrder $traderOrder, $step): void
    {
        $stepToHistoriesMap = get_murabha_steps($traderOrder->provider, $traderOrder->version, $traderOrder->contract_signed_type, true);

        if (! array_key_exists($step, $stepToHistoriesMap)) {
            throw new InvalidArgumentException;
        }

        foreach ($stepToHistoriesMap[$step] as $history => $media) {
            if ($media && isset($data[$media['file']])) {
                $this->attachDocumentToOrder(
                    $traderOrder,
                    base64_encode(file_get_contents($data[$media['file']])),
                    $media['collection'],
                    'base64'
                );
            }

            if (! $traderOrder->checkOrderHistoryAction($history)) {
                $this->createTraderOrderHistory($traderOrder, $history);
            }
        }
    }

    public function createTraderOrder(FinancingOrder $financingOrder, string $ttiId, string $provider): Model|TraderOrder
    {
        return $financingOrder->traderOrders()->create([
            'provider' => $provider,
            'reference' => $ttiId,
            'status' => TraderOrderStatus::InProgress,
            'mode' => TraderOrderMode::Automatic,
            'version' => get_latest_version_of_trader($provider),
        ]);
    }

    public function updateOrderStatus($order, $status): void
    {
        $order->update([
            'status' => $status,
        ]);
    }

    public function createTraderOrderHistory(TraderOrder $traderOrder, int $action, array $data = []): void
    {
        Log::info('Creating trader order history', [
            'trader_order_id' => $traderOrder->id,
            'action' => $action,
            'data' => $data,
        ]);
        $traderOrder->traderHistories()->updateOrCreate(
            [
                'action' => $action,
            ],
            $data
        );
    }

    public function storeOrderDocumentAsPdf(string $view, array $data, TraderOrder $traderOrder, $mediaCollection): void
    {
        Log::info('Storing order document as pdf', [
            'view' => $view,
            'trader_order_id' => $traderOrder->id,
        ]);
        $html = view($view, $data)->render();

        PdfGenerator::outputFromHtml($html, function ($fileResource) use ($mediaCollection, $traderOrder) {
            $this->attachDocumentToOrder(
                $traderOrder,
                $fileResource,
                $mediaCollection
            );
        });
    }

    public function attachDocumentToOrder($traderOrder, $document, $collectionName, $type = null, $originalFileName = null): void
    {
        $traderManager = new TraderManager(app());
        $fileName = $originalFileName ?? $traderManager->driver($traderOrder->provider)->generatePdfFileName($traderOrder, $collectionName);
        $media = $type
            ? $traderOrder->addMediaFromBase64($document)
            : $traderOrder->addMediaFromStream($document);

        $media->usingFileName($fileName)->toMediaCollection($collectionName);
    }

    public function transformProductsToCommodityProductsDTO($products): Collection
    {
        return collect($products)->map(function ($product) {
            return CommodityProductDto::fromArray([
                'product' => $product['product'],
                'quantity' => $product['quantity'],
                'uom' => $product['uom'],
                'amount' => $product['amount'],
                'warehouse' => $product['warehouse'],
                'previous_owner' => $product['previous_owner'],
                'date_time_of_purchasing_commodity' => $product['date_time_of_purchasing_commodity'],
            ]);
        });
    }

    public function transformProductsToLocalCommodityProductsDTO($products, string|array|null $groupByKeys = null): Collection
    {

        return collect($products)
            ->when($groupByKeys, function (Collection $productCollection) use ($groupByKeys) {
                $keys = (array) $groupByKeys;

                return $productCollection
                    ->groupBy(fn ($item) => $this->generateGroupKey($item, $keys))
                    ->map(fn (Collection $group) => $this->mapGroupToDto($group));
            }, function (Collection $productCollection) {
                return $productCollection->map(fn (array $product) => $this->mapProductToDto($product));
            });

    }

    private function generateGroupKey(array $item, array $keys): string
    {
        return implode('|', array_map(fn (string $key) => $item[$key] ?? '', $keys));
    }

    private function mapGroupToDto(Collection $group): LynkCommodityProductDto
    {
        $firstItem = $group->first();

        return LynkCommodityProductDto::fromArray([
            'product' => $firstItem['product'],
            'type' => $firstItem['type'],
            'quantity' => $group->sum('quantity'),
            'uom' => $firstItem['uom'],
            'amount' => $group->sum('amount'),
            'location' => $firstItem['location'],
            'currency' => $firstItem['currency'],
            'original_supplier' => $firstItem['original_supplier'],
            'previous_owner' => $firstItem['previous_owner'],
        ]);
    }

    private function mapProductToDto(array $product): LynkCommodityProductDto
    {
        return LynkCommodityProductDto::fromArray($product);
    }

    /**
     * Get an unused product code for a given trader order.
     *
     * First checks for company override international commodity type (forced choice).
     * If override is set and available, returns only that product code.
     * If override is set but unavailable, returns null to force cancellation.
     * Otherwise, falls back to preferred product codes or global defaults.
     *
     * @param  TraderOrder  $traderOrder  The trader order to find a product code for
     * @return string|null The first available product code or null if no codes are available
     */
    public function getUnusedProductCode(TraderOrder $traderOrder): ?string
    {
        Log::info('Getting unused product code', [
            'trader_order_id' => $traderOrder->id,
            'provider' => $traderOrder->provider,
            'company_id' => $traderOrder->order->company_id,
        ]);

        $productCodes = $this->getProductCodes($traderOrder);

        $selectedCode = ! empty($productCodes) ? reset($productCodes) : null;

        Log::info('Product code selection result', [
            'trader_order_id' => $traderOrder->id,
            'selected_product_code' => $selectedCode,
            'available_codes_count' => count($productCodes),
            'all_available_codes' => $productCodes,
        ]);

        return $selectedCode;
    }

    /**
     * Retrieve product codes based on various filtering criteria.
     *
     * @param  TraderOrder  $traderOrder  The trader order to base product code selection on
     * @return array List of filtered and sorted product codes
     */
    private function getProductCodes(TraderOrder $traderOrder): array
    {
        Log::info('Starting product code selection', [
            'trader_order_id' => $traderOrder->id,
            'provider' => $traderOrder->provider,
            'company_id' => $traderOrder->order->company_id,
        ]);

        // Fallback to existing logic if no override is set
        $query = TraderProduct::query();

        $globalPreferredCommodityType = app(InternationalMurabahaSetting::class)
            ->bursam_default_preferred_commodity_type;

        Log::info('Global preferred commodity type retrieved', [
            'trader_order_id' => $traderOrder->id,
            'global_preferred_commodity_type_id' => $globalPreferredCommodityType,
        ]);

        if ($globalPreferredCommodityType) {
            $query = $query->orderByRaw(
                'CASE WHEN id = ? THEN 0 ELSE 1 END',
                [$globalPreferredCommodityType]
            );
        }

        if ($traderOrder->provider) {
            $query = $query->where('provider', $traderOrder->provider);
        }

        $companyPreferredProductCodes = $this->getCompanyPreferredProductCodes($traderOrder);

        Log::info('Company preferred product codes retrieved', [
            'trader_order_id' => $traderOrder->id,
            'company_preferred_product_codes' => $companyPreferredProductCodes,
            'count' => count($companyPreferredProductCodes),
        ]);

        $unavailableProductCodes = Cache::get('bursam_unavailable_product_codes', []);
        if (! is_array($unavailableProductCodes)) {
            $unavailableProductCodes = [];
        }

        Log::info('Unavailable product codes from cache', [
            'trader_order_id' => $traderOrder->id,
            'unavailable_product_codes' => $unavailableProductCodes,
            'count' => count($unavailableProductCodes),
        ]);

        if (! empty($companyPreferredProductCodes)) {
            $availablePreferredProductCodes = array_diff($companyPreferredProductCodes, $unavailableProductCodes);

            Log::info('Available preferred product codes after filtering unavailable', [
                'trader_order_id' => $traderOrder->id,
                'available_preferred_product_codes' => $availablePreferredProductCodes,
                'count' => count($availablePreferredProductCodes),
                'filtered_out' => array_intersect($companyPreferredProductCodes, $unavailableProductCodes),
            ]);

            if (empty($availablePreferredProductCodes)) {
                Log::warning('All preferred product codes are unavailable', [
                    'trader_order_id' => $traderOrder->id,
                    'preferred_codes' => $companyPreferredProductCodes,
                    'unavailable_codes' => $unavailableProductCodes,
                ]);

                return []; // All preferred codes are unavailable
            }

            $query = $query->whereIn('code', $availablePreferredProductCodes);
        } else {
            Log::info('No company preferred product codes found, using global filtering', [
                'trader_order_id' => $traderOrder->id,
            ]);

            // No preferred product codes; exclude unavailable ones globally
            if (! empty($unavailableProductCodes)) {
                $query = $query->whereNotIn('code', $unavailableProductCodes);
            }
        }

        $finalProductCodes = $query
            ->orderBy('order', 'asc')
            ->pluck('code')
            ->toArray();

        Log::info('Final product codes selection completed', [
            'trader_order_id' => $traderOrder->id,
            'final_product_codes' => $finalProductCodes,
            'count' => count($finalProductCodes),
            'selected_first' => ! empty($finalProductCodes) ? $finalProductCodes[0] : null,
            'selection_criteria' => [
                'had_company_preferences' => ! empty($companyPreferredProductCodes),
                'had_global_preference' => ! empty($globalPreferredCommodityType),
                'had_unavailable_codes' => ! empty($unavailableProductCodes),
                'provider' => $traderOrder->provider,
            ],
        ]);

        return $finalProductCodes;
    }

    /**
     * Retrieve preferred product codes for a company associated with a trader order.
     *
     * @param  TraderOrder  $traderOrder  The trader order to extract company from
     * @return array List of preferred product codes
     */
    public function getCompanyPreferredProductCodes(TraderOrder $traderOrder): array
    {
        Log::info('Retrieving company preferred product codes', [
            'trader_order_id' => $traderOrder->id,
            'company_id' => $traderOrder->order->company_id,
        ]);

        $companyPreferredProductIds = $traderOrder->order->company
            ->preferredBursaProducts
            ->pluck('id')
            ->toArray();

        Log::info('Company preferred product IDs retrieved', [
            'trader_order_id' => $traderOrder->id,
            'company_id' => $traderOrder->order->company_id,
            'preferred_product_ids' => $companyPreferredProductIds,
            'count' => count($companyPreferredProductIds),
        ]);

        $productCodes = TraderProduct::whereIn('id', $companyPreferredProductIds)
            ->pluck('code')
            ->toArray();

        Log::info('Company preferred product codes resolved', [
            'trader_order_id' => $traderOrder->id,
            'company_id' => $traderOrder->order->company_id,
            'product_codes' => $productCodes,
            'count' => count($productCodes),
        ]);

        return $productCodes;
    }
}
