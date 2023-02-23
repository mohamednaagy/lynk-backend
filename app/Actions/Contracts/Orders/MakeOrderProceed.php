<?php

namespace App\Actions\Contracts\Orders;

use App\Models\TraderOrder;
use Illuminate\Http\UploadedFile;

interface MakeOrderProceed
{
    public function handle(TraderOrder $traderOrder, string $case, bool $forceToProceed);

    public function setClientWakala(UploadedFile $clientWakalaFile);
}
