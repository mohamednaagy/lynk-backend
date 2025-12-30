<?php

namespace App\Support\Webhooks;

use App\Models\Lender;
use Spatie\WebhookServer\WebhookCall;

class WebhookEventManager
{
    /**
     * fire lender webhook by webhook type
     *
     * @return void
     */
    public function fire(Lender $lender, string $webhookType, array $payload)
    {
        $lender->webhooks()
            ->whereType($webhookType)
            ->chunk(
                50,
                function ($webhooks) use ($lender, $payload) {
                    $webhooks->each(
                        function ($webhook) use ($lender, $payload) {
                            WebhookCall::create()
                                ->url($webhook->url)
                                ->payload($payload)
                                ->useSecret($lender->lenderDetail->webhook_secret_key)
                                ->dispatch();
                        }
                    );
                }
            );
    }
}
