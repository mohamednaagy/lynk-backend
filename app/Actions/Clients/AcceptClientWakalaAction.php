<?php

namespace App\Actions\Clients;

use App\Actions\Contracts\Clients\AcceptClientWakala;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
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

        $media = PdfGenerator::outputFromHtml($wakalaTemplate, $path, function ($fileResource) use ($order) {
            return $order->addMedia($fileResource)
                ->toMediaCollection(FinancingOrderMediaCollection::ClientWakala);
        });

        $order->update([
            'client_wakala_accepted_at' => now(),
        ]);

        return $media;
    }
}
