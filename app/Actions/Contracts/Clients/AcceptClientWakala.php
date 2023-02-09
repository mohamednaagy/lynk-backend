<?php

namespace App\Actions\Contracts\Clients;

use App\Models\TraderOrder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

interface AcceptClientWakala
{
    /**
     * @param  TraderOrder  $traderOrder
     * @return Media
     */
    public function handle(TraderOrder $traderOrder): Media;
}
