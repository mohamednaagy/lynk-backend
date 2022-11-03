<?php

namespace App\Actions\Clients;

use App\Actions\Contracts\Clients\AcceptClientWakala;
use App\Models\FinancingOrder;
use App\Support\PdfGenerator\PdfGenerator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\UnauthorizedException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AcceptClientWakalaAction implements AcceptClientWakala
{
    public function handle(FinancingOrder $order, string $token): Media
    {
        $tokenCacheKey = sprintf('client_wakala_token_%s_%s', $order->id, $order->getNationalId());

        $hashedToken = Cache::get($tokenCacheKey);

        if (! Hash::check($token, $hashedToken)) {
            throw new UnauthorizedException();
        }

        $wakalaTemplate = view('templates.client-wakala', [
            'clientName' => $order->customer_details['englishName'],
        ])->render();

        $path = $order->id.'/client-wakala/'.$order->getNationalId().'.pdf';

        PdfGenerator::outputFromHtml($wakalaTemplate, $path, [
            'gotoOptions' => ['waitUntil' => 'networkidle0'],
        ]);

        $order->update([
            'client_wakala_accepted_at' => now(),
        ]);

        Cache::forget($tokenCacheKey);

        return $order->addMedia(storage_path('app/'.$path))->toMediaCollection('client_wakala');
    }
}
