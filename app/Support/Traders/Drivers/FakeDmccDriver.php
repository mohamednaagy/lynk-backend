<?php

namespace App\Support\Traders\Drivers;

use App\Enums\FinancingOrderHistory;
use App\Enums\TraderOrderStatus;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\PdfGenerator\PdfGenerator;
use App\Support\Traders\Contracts\TraderInterface;
use Illuminate\Database\Eloquent\Model;

class FakeDmccDriver implements TraderInterface
{
    public function acceptAgreement(): bool
    {
        return true;
    }

    public function getTti(FinancingOrder $financingOrder): string
    {
        $ttiId = $this->getTtiId($financingOrder);
        $traderOrder = $this->createTraderOrder($financingOrder, $ttiId);
        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::GetTtiId);

        return $ttiId;
    }

    public function fetchNotification(string $type): ?array
    {
        return [];
    }

    public function processFetchNotification($notificationId): void
    {

    }

    public function getTtiId(FinancingOrder $financingOrder): mixed
    {
        return '2023';
    }

    private function createTraderOrder(FinancingOrder $financingOrder, string $ttiId): Model|TraderOrder
    {
        return $financingOrder->traderOrders()->create([
            'provider' => 'dmcc',
            'reference' => $ttiId,
            'status' => TraderOrderStatus::InProgress,
        ]);
    }

    public function respondPtpService(string $ttiId): void
    {

    }

    public function createSellingCommodityToCustomerDocument($traderOrder): void
    {
        $html = view('selling-commodity-to-customer')->render();
        $path = $traderOrder->financing_order_id.'/DMCC-SCTC/'.$traderOrder->reference.'.pdf';
        PdfGenerator::outputFromHtml($html, $path, [
            'gotoOptions' => ['waitUntil' => 'networkidle0'],
        ]);

        $this->attachDocumentToOrder($traderOrder, storage_path('app/'.$path), 'selling_commodity_to_customer');
    }

    public function getDocumentByTypeAndTransaction(string $ttiId, string $documentType): mixed
    {
        return '';
    }

    public function attachDocumentToOrder($traderOrder, $document, $collectionName, $type = null): void
    {
        if (! is_null($type)) {
            $traderOrder->order->addMediaFromBase64(
                $document
            )->toMediaCollection($collectionName);
        } else {
            $traderOrder->order->addMedia(
                $document
            )->toMediaCollection($collectionName);
        }
    }

    public function updateOrderStatus($order, int $status): void
    {
        $order->update([
            'status' => $status,
        ]);
    }

    public function createTransferOwnershipToLenderDocument($traderOrder): void
    {
        $html = view('transfer-ownership-to-lender')->render();
        $path = $traderOrder->financing_order_id.'/DMCC-TOTL/'.$traderOrder->reference.'.pdf';
        PdfGenerator::outputFromHtml($html, $path, [
            'gotoOptions' => ['waitUntil' => 'networkidle0'],
        ]);

        $this->attachDocumentToOrder($traderOrder, storage_path('app/'.$path), 'transfer_ownership_to_lender');
    }

    // TODO: check with a.medhat
    public function createTraderOrderHistory(TraderOrder $traderOrder, int $action): void
    {
        $traderOrder->traderHistories()->create([
            'action' => $action,
        ]);
    }

    public function uploadTTIDocumentAndGetVersionNumber(string $ttiId): mixed
    {
        return '009';
    }

    public function issueMurabahaPurchaseOffer(string $ttiId, string $versionNo): void
    {

    }
}
