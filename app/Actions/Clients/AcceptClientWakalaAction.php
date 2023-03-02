<?php

namespace App\Actions\Clients;

use App\Actions\Contracts\Clients\AcceptClientWakala;
use App\Enums\FinancingOrderHistory;
use App\Models\TraderOrder;
use App\Support\Traders\TraderHelperTrait;

class AcceptClientWakalaAction implements AcceptClientWakala
{
    use TraderHelperTrait;

    public function handle(TraderOrder $traderOrder): void
    {
        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::ClientWakalaAccepted);
    }
}
