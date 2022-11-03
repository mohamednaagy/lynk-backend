<?php

namespace App\Actions\Contracts\Clients;

use App\Models\FinancingOrder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

interface AcceptClientWakala
{
    /**
     * @param  FinancingOrder  $order
     * @param  string  $token
     * @return Media
     */
    public function handle(FinancingOrder $order, string $token): Media;
}
