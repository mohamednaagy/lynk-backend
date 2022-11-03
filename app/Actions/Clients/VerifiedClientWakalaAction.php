<?php

namespace App\Actions\Clients;

use App\Actions\Contracts\Clients\VerifiedClientWakala;
use App\Models\FinancingOrder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class VerifiedClientWakalaAction implements VerifiedClientWakala
{
    public function handle(FinancingOrder $order): array
    {
        $token = Str::random(45);
        Cache::put(sprintf('client_wakala_token_%s_%s', $order->id, $order->getNationalId()), Hash::make($token), 60 * 5);

        $wakalaTemplate = view('templates.client-wakala', [
            'clientName' => $order->customer_details['englishName'],
        ])->render();

        return [
            'token' => $token,
            'template' => $wakalaTemplate,
        ];
    }
}
