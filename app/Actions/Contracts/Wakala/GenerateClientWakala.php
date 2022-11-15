<?php

namespace App\Actions\Contracts\Wakala;

use App\Models\FinancingOrder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

interface GenerateClientWakala
{
    public function handle(FinancingOrder $financingOrder): Media;
}
