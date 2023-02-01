<?php

namespace App\Support\Traders;

use App\Enums\TraderOrderStatus;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\PdfGenerator\PdfGenerator;
use Illuminate\Database\Eloquent\Model;

trait TraderHelperTrait
{
    public function createTraderOrder(FinancingOrder $financingOrder, string $ttiId, string $provider): Model|TraderOrder
    {
        return $financingOrder->traderOrders()->create([
            'provider' => $provider,
            'reference' => $ttiId,
            'status' => TraderOrderStatus::InProgress,
        ]);
    }

    public function updateOrderStatus($order, int $status): void
    {
        $order->update([
            'status' => $status,
        ]);
    }

    public function createTraderOrderHistory(TraderOrder $traderOrder, int $action): void
    {
        $traderOrder->traderHistories()->create([
            'action' => $action,
        ]);
    }

    public function storeOrderDocumentAsPdf(string $view, array $data, TraderOrder $traderOrder, $mediaCollection): void
    {
        $html = view($view, $data)->render();

        PdfGenerator::outputFromHtml($html, function ($fileResource) use ($mediaCollection, $traderOrder) {
            $this->attachDocumentToOrder(
                $traderOrder,
                $fileResource,
                $mediaCollection
            );
        });
    }

    public function attachDocumentToOrder($traderOrder, $document, $collectionName, $type = null): void
    {
        $fileName = $traderOrder->provider.'-'.$traderOrder->reference.'.pdf';
        if (! is_null($type)) {
            $traderOrder->addMediaFromBase64(
                $document
            )->usingFileName($fileName)->toMediaCollection($collectionName);
        } else {
            $traderOrder->addMediaFromStream(
                $document
            )->usingFileName($fileName)->toMediaCollection($collectionName);
        }
    }
}
