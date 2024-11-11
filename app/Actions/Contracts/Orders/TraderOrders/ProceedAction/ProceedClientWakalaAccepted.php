<?php

namespace App\Actions\Contracts\Orders\TraderOrders\ProceedAction;

use App\Models\TraderOrder;
use Illuminate\Http\UploadedFile;

interface ProceedClientWakalaAccepted
{
    public function handle(TraderOrder $traderOrder, ?UploadedFile $signedClientWakala = null, bool $forceToProceed = false): array;
}
