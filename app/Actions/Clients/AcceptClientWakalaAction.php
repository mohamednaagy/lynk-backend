<?php

namespace App\Actions\Clients;

use App\Actions\Contracts\Clients\AcceptClientWakala;
use App\Actions\Contracts\Wakala\GenerateClientWakala;
use App\Enums\FinancingOrderHistory;
use App\Models\TraderOrder;
use App\Support\Traders\Traits\DmccTraderHelperTrait;

class AcceptClientWakalaAction implements AcceptClientWakala
{
    use DmccTraderHelperTrait;

    public function handle(TraderOrder $traderOrder): void
    {
        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::ClientWakalaAccepted);
        app(GenerateClientWakala::class)->handle($traderOrder);
    }
}
