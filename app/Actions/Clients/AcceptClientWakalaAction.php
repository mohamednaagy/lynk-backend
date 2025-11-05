<?php

namespace App\Actions\Clients;

use App\Actions\Contracts\Clients\AcceptClientWakala;
use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Models\TraderOrder;
use App\Support\Traders\Traits\TraderHelperTrait;
use Illuminate\Http\UploadedFile;

class AcceptClientWakalaAction implements AcceptClientWakala
{
    use TraderHelperTrait;

    public function handle(TraderOrder $traderOrder, ?UploadedFile $signedClientWakala = null): void
    {
        $this->attachSignedClientWakalaIfProvided($traderOrder, $signedClientWakala);
        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::ClientWakalaAccepted);
    }

    /**
     * Attach the signed client wakala file to the trader order if provided.
     *
     *
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    protected function attachSignedClientWakalaIfProvided(TraderOrder $traderOrder, ?UploadedFile $signedClientWakala): void
    {
        if ($signedClientWakala === null) {
            return;
        }

        $traderOrder->addMedia($signedClientWakala)
            ->toMediaCollection(TraderOrderMediaCollection::SignedClientWakala);
    }
}
