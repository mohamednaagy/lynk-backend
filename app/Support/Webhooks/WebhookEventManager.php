<?php

namespace App\Support\Webhooks;

use App\Models\Company;
use Spatie\WebhookServer\WebhookCall;

class WebhookEventManager
{
    public function __construct(protected Company $company, protected string $webhookType, protected array $payload)
    {
    }

    public function fireEvents()
    {
        $this->company
            ->webhooks()
            ->whereType($this->webhookType)->chunk(
            50,
            function ($webhooks) {
                $webhooks->map(function ($webhook) {
                    WebhookCall::create()
                        ->url($webhook->url)
                        ->payload($this->payload)
                        ->useSecret($webhook->company->webhook_secret_key)
                        ->dispatch();
                });
            }
        );
    }
}
