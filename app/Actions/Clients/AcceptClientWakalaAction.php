<?php

namespace App\Actions\Clients;

use App\Actions\Contracts\Clients\AcceptClientWakala;
use App\Actions\Contracts\Wakala\GenerateClientWakala;
use App\Models\FinancingOrder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AcceptClientWakalaAction implements AcceptClientWakala
{
    public function __construct(protected GenerateClientWakala $generateClientWakala)
    {
    }

    public function handle(FinancingOrder $order): Media
    {
        $order->update([
            'client_wakala_accepted_at' => now(),
        ]);

        return $this->generateClientWakala->handle($order);
    }
}
