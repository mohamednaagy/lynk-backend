<?php

namespace App\Actions\Contracts\LocalMarket;

use App\Models\TraderOrder;

interface SellConfirmation
{
    public function handle(TraderOrder $reference): void;
}
