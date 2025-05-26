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
use Illuminate\Support\Arr;
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
     * Get an unused product code for a given provider.
     *
     * @param  string  $provider  The provider for which to find a product code
     * @return string|null The first available product code
     */
    public function getUnusedProductCode(string $provider): ?string
    {
        // Retrieve product codes for the provider
        $productCodes = $this->getProductCodes($provider);
        Log::info('productCodes', [$productCodes]);

        // Get cached list of unavailable product codes, defaulting to an empty array
        $unavailableProductCodes = Cache::get('bursam_unavailable_product_codes', []);

        // Find available product codes by removing unavailable ones
        $availableProductCodes = array_diff($productCodes, $unavailableProductCodes);

        // Return the first available product code, or the first non-empty code if no available codes
        return Arr::first(
            empty($availableProductCodes)
                ? array_filter($productCodes)
                : $availableProductCodes
        );
    }

    /**
     * Retrieve product codes with optional filtering and sorting.
     *
     * @param  string|null  $provider  The provider to filter by
     * @return array List of product codes
     */
    private function getProductCodes(?string $provider): array
    {

        // Start with a base query for TraderProduct
        $query = TraderProduct::query();

        // Retrieve the default preferred commodity type
        $bursam_default_preferred_commodity_type = app(InternationalMurabahaSetting::class)
            ->bursam_default_preferred_commodity_type;

        // Apply commodity type filtering/sorting if a preferred type exists
        if ($bursam_default_preferred_commodity_type) {
            Log::info('bursam_default_preferred_commodity_type', [$bursam_default_preferred_commodity_type]);
            $query->orderByRaw(
                'CASE WHEN id = ? THEN 0 ELSE 1 END',
                [$bursam_default_preferred_commodity_type]
            );
        }

        // Filter by provider if provided
        if ($provider) {
            $query->where('provider', $provider);
        }

        // Return sorted product codes
        return $query->orderBy('order', 'asc')->pluck('code')->toArray();
    }
}
