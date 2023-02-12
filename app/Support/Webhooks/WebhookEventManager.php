<?php

namespace App\Support\Webhooks;

use App\Models\Company;
use Spatie\WebhookServer\WebhookCall;

class WebhookEventManager
{
    /**
     * fire company webhook by webhook type
     *
     * @param  Company  $company
     * @param  string  $webhookType
     * @param  array  $payload
     * @return void
     */
    public function fire(Company $company, string $webhookType, array $payload)
    {
        $company->webhooks()
            ->whereType($webhookType)
            ->chunk(
                50,
                function ($webhooks) use ($company, $payload) {
                    $webhooks->each(
                        function ($webhook) use ($company, $payload) {
                            WebhookCall::create()
                                ->url($webhook->url)
                                ->payload($payload)
                                ->useSecret($company->webhook_secret_key)
                                ->dispatch();
                        }
                    );
                }
            );
    }
}
