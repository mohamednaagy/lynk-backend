<?php

namespace App\Actions\Clients;

use App\Actions\Contracts\Clients\VerifiedClientWakala;
use App\Actions\Contracts\Wakala\GetClientWakalaText;
use App\Actions\Contracts\Wakala\GetWakalaTemplate;
use App\Models\FinancingOrder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class VerifiedClientWakalaAction implements VerifiedClientWakala
{
    public function __construct(
        protected GetWakalaTemplate $getWakalaTemplate,
        protected GetClientWakalaText $getClientWakalaText
    ) {
    }

    public function handle(FinancingOrder $order): array
    {
        $token = Str::random(100);

        Cache::put(
            sprintf('client_wakala_token_%s_%s', $order->id, $order->getNationalId()),
            Hash::make($token),
            now()->addMinutes(10)
        );

        $traderOrder = $order->activeTraderOrder()->first();

        $lenderTemplate = $this->getWakalaTemplate->handle('client')['wakala_template'];
        $template = $this->getClientWakalaText->handle($traderOrder, $lenderTemplate);

        return [
            'token' => $token,
            'template' => $template,
        ];
    }
}
