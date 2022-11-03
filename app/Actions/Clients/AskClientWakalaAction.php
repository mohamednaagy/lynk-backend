<?php

namespace App\Actions\Clients;

use App\Actions\Contracts\Clients\AskClientWakala;
use App\Models\FinancingOrder;
use App\Support\Sms\Sms;
use Illuminate\Support\Facades\URL;
use Shivella\Bitly\Facade\Bitly;

class AskClientWakalaAction implements AskClientWakala
{
    public function handle(FinancingOrder $order, string $url): bool
    {
        $url = URL::signedExternalRoute(
            $url,
            'client.order.verify',
            [
                'order' => $order,
                'national_id' => $order->getNationalId(),
            ]
        );

        $shortUrl = Bitly::getUrl($url);

        try {
            Sms::send(
                sprintf('You have received Wakala request %s', $shortUrl),
                ltrim($order->getPhoneNumber()->formatE164(), '+')
            );

            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }
}
