<?php

namespace App\Support\Traders\Contracts;

use App\Models\TraderOrder;

interface SellConfirmationCertifiable
{
    public function createSellConfirmationDocument(TraderOrder $traderOrder): void;
}
