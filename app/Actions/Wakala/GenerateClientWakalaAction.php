<?php

namespace App\Actions\Wakala;

use App\Actions\Contracts\Wakala\GenerateClientWakala;
use App\Models\TraderOrder;
use App\Support\DocumentEngine\PdfFactory;

class GenerateClientWakalaAction implements GenerateClientWakala
{
    public function handle(TraderOrder $traderOrder)
    {
        $pdf = PdfFactory::make('client_wakala');
        $pdf->setContext([
            'traderOrder' => $traderOrder,
        ]);
        $pdf->generate();
    }
}
