<?php

namespace App\Actions\Clients;

use App\Actions\Contracts\Clients\AcceptClientWakala;
use App\Actions\Contracts\Wakala\GenerateClientWakala;
use App\Models\TraderOrder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AcceptClientWakalaAction implements AcceptClientWakala
{
    public function __construct(protected GenerateClientWakala $generateClientWakala)
    {
    }

    public function handle(TraderOrder $traderOrder): Media
    {
        if ($traderOrder->client_wakala_accepted_at === null) {
            $traderOrder->update([
                'client_wakala_accepted_at' => now(),
            ]);
        }

        return $this->generateClientWakala->handle($traderOrder);
    }
}
