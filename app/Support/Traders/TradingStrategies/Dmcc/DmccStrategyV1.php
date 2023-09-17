<?php

namespace App\Support\Traders\TradingStrategies\Dmcc;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;

class DmccStrategyV1 extends BaseDmccStrategy
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
        FinancingOrderHistory::AttachWarrantAmendmentExceptWarrantNoDocument => [
            'collection' => TraderOrderMediaCollection::WarrantAmendmentExceptWarrantNo,
            'file' => 'document',
        ],
        FinancingOrderHistory::AttachMpoDocument => [
            'collection' => TraderOrderMediaCollection::MurabahaPurchaseOrder,
            'file' => 'document',
        ],
    ];
}
