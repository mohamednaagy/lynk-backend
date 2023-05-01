<?php

namespace App\Actions\Clients;

use App\Actions\Contracts\Clients\AcceptClientWakala;
use App\Actions\Contracts\Wakala\GenerateClientWakala;
use App\Enums\DmccMurabhaStep;
use App\Models\TraderOrder;
use App\Support\Traders\Traits\DmccTraderHelperTrait;

class AcceptClientWakalaAction implements AcceptClientWakala
{
    use DmccTraderHelperTrait;

    public function handle(TraderOrder $traderOrder): void
    {
        $this->createStepHistories(request: request(), traderOrder: $traderOrder, step: DmccMurabhaStep::ClientWakala);
        app(GenerateClientWakala::class)->handle($traderOrder);
    }
}
