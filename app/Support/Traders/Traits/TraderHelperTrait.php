<?php

namespace App\Support\Traders\Traits;

use App\Enums\FinancingOrderProceedCase;
use App\Enums\TraderOrderMode;
use App\Enums\TraderOrderStatus;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Services\TraderOrder\TraderOrderProceedCaseService;
use App\Support\DataTransferObjects\LynkCommodityProductDto;
use App\Support\Traders\TraderManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
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

    public function createTraderOrder(FinancingOrder $financingOrder, string $ttiId, string $provider, ?int $preferredCommodityTypeId = null): Model|TraderOrder
    {
        return $financingOrder->traderOrders()->create([
            'provider' => $provider,
            'reference' => $ttiId,
            'status' => TraderOrderStatus::InProgress,
            'mode' => TraderOrderMode::Automatic,
            'version' => get_latest_version_of_trader($provider),
            'commodity_type_id' => $preferredCommodityTypeId,
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

        Log::info(formatLogTitle('Creating trader order history', $traderOrder), [
            'financingOrderId' => $traderOrder->financing_order_id,
            'traderOrderId' => $traderOrder->id,
            'action' => $action,
            'data' => $data,
        ]);
        if ($traderOrder->traderHistories()->where('action', $action)->exists()) {
            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))
                ->info(formatLogTitle('Trader order history action already exists', $traderOrder), [
                    'traderOrderId' => $traderOrder->id,
                    'action' => $action,
                    'data' => $data,
                ]);

            return;
        }
        $traderOrder->traderHistories()->create(
            [
                'action' => $action,
                'data' => $data,
            ]
        );
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

    protected function isContractAndWakalaCompleted(TraderOrder $traderOrder): bool
    {
        return app(TraderOrderProceedCaseService::class)
            ->checkIfTraderHasCase(
                $traderOrder->id,
                FinancingOrderProceedCase::ContractAndClientWakalaCompleted
            );
    }
}
