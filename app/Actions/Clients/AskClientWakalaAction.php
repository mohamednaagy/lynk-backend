<?php

namespace App\Actions\Clients;

use App\Actions\Contracts\Clients\AskClientWakala;
use App\Models\FinancingOrder;
use Illuminate\Support\Facades\URL;

class AskClientWakalaAction implements AskClientWakala
{
    public function handle(FinancingOrder $order, string $url): bool
    {
        $url = URL::signedExternalRoute(
            $url,
            'api.v1.verify.client.wakala',
            [
                'order' => $order,
                'national_id' => $order->getNationalId(),
            ]
        );

        if (app()->isProduction()) {
            app('bitly')->getUrl($url);
        }

        return true;
    }
}
