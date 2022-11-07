<?php

namespace App\Actions\Clients;

use App\Actions\Contracts\Clients\AcceptClientWakala;
use App\Enums\FinancingOrderMediaCollection;
use App\Models\FinancingOrder;
use App\Support\PdfGenerator\PdfGenerator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AcceptClientWakalaAction implements AcceptClientWakala
{
    public function handle(FinancingOrder $order): Media
    {
        $wakalaTemplate = view('templates.client-wakala', [
            'clientName' => $order->customer_details['englishName'] ?? '',
        ])->render();

        $path = $order->id.'/client-wakala/'.$order->getNationalId().'.pdf';

        PdfGenerator::outputFromHtml($wakalaTemplate, $path, [
            'gotoOptions' => ['waitUntil' => 'networkidle0'],
        ]);

        $order->update([
            'client_wakala_accepted_at' => now(),
        ]);

        return $order->addMedia(storage_path('app/'.$path))->toMediaCollection(FinancingOrderMediaCollection::ClientWakala);
    }
}
