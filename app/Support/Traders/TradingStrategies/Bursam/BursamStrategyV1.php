<?php

namespace App\Support\Traders\TradingStrategies\Bursam;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;

class BursamStrategyV1 extends BaseBursamStrategy
{
    public static string $version = 'v1';

    public array $historySteFileMap = [
        FinancingOrderHistory::AttachPtpDocumentToOrder => [
            'collection' => TraderOrderMediaCollection::PromiseToPurchase,
            'file' => 'ptp_document',
        ],
        FinancingOrderHistory::AttachTtiHoldingCertificateDocument => [
            'collection' => TraderOrderMediaCollection::TtiHoldingCertificate,
            'file' => 'original_holding_certificate',
        ],
        FinancingOrderHistory::AttachMpoDocument => [
            'collection' => TraderOrderMediaCollection::MurabahaPurchaseOrder,
            'file' => 'document',
        ],
        FinancingOrderHistory::AttachWarrantAmendmentExceptWarrantNoDocument => [
            'collection' => TraderOrderMediaCollection::WarrantAmendmentExceptWarrantNo,
            'file' => 'document',
        ],
    ];
}
