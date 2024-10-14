<?php

namespace App\Support\Traders\TradingStrategies\Contracts;

use App\Models\TraderOrder;
use Illuminate\Http\Request;

interface TraderStrategyInterface
{
    public function updatePurchasingCommodity(TraderOrder $traderOrder, array $data);

    public function updateMurabahaPurchaseOffer(TraderOrder $traderOrder, Request $request);

    public function updateCommodityCertificateForClient(TraderOrder $traderOrder, Request $request);

    public function updateMurabhaCompleteDocument(TraderOrder $traderOrder, array $data);

    public function updateSellConfirmationDocument(TraderOrder $traderOrder, Request $request);
}
