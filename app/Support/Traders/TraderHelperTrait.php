<?php

namespace App\Support\Traders;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\TraderOrderStatus;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\PdfGenerator\PdfGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait TraderHelperTrait
{
    public array $stepToHistoriesMap = [
        FinancingOrderStatus::CommodityPurchased => [
            FinancingOrderHistory::RespondPtp => null,
            FinancingOrderHistory::GetPtpDocument => null,
            FinancingOrderHistory::GetTtiHoldingCertificateDocument => null,
            FinancingOrderHistory::AttachPtpDocumentToOrder => [
                'collection' => TraderOrderMediaCollection::PromiseToPurchase,
                'file' => 'ptp_document',
            ],
            FinancingOrderHistory::AttachTtiHoldingCertificateDocument => [
                'collection' => TraderOrderMediaCollection::TtiHoldingCertificate,
                'file' => 'original_holding_certificate',
            ],
        ],
        FinancingOrderStatus::MurabhaOfferIssued => [
            FinancingOrderHistory::IssueMurabahaOffer => null,
            FinancingOrderHistory::GetMurabahaPurchaseOfferDocument => null,
            FinancingOrderHistory::AttachMpoDocument => [
                'collection' => TraderOrderMediaCollection::MurabahaPurchaseOrder,
                'file' => 'document',
            ],
        ],
    ];

    public function createStepHistories(Request $request, $trader, TraderOrder $traderOrder, $status)
    {
        foreach ($this->stepToHistoriesMap[$status] as $history => $media) {
            if ($media && $request->has($media['file'])) {
                $this->attachDocumentToOrder(
                    $traderOrder,
                    base64_encode(file_get_contents($request->file($media['file']))),
                    $media['collection'],
                    'base64'
                );
            }

            if (! $traderOrder->checkOrderHistoryAction($history)) {
                $trader->createTraderOrderHistory($traderOrder, $history);
            }
        }
    }

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
        $traderOrder->traderHistories()->updateOrCreate(
            [
                'action' => $action,
            ],
            [
                'updated_at' => now(),
            ]
        );
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
