<?php

namespace App\Actions\Contracts\Clients;

use App\Models\TraderOrder;
use Illuminate\Http\UploadedFile;

interface AcceptClientWakala
{
    public function handle(TraderOrder $traderOrder, ?UploadedFile $signedClientWakala = null): void;
}
