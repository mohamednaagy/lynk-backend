<?php

namespace App\Actions\Webhooks;

use App\Actions\Contracts\Webhooks\GenerateWebhookSecretKey;
use App\Actions\Contracts\Webhooks\UpdateWebhookSecretKey;
use App\Models\Lender;

class UpdateWebhookSecretKeyAction implements UpdateWebhookSecretKey
{
    public function __construct(protected GenerateWebhookSecretKey $generateWebhookSecretKey) {}

    public function handle(Lender $lender): Lender
    {
        $lender->lenderDetail()->updateOrCreate(
            ['company_id' => $lender->id],
            [
                'webhook_secret_key' => $this->generateWebhookSecretKey->handle(),
            ]
        );

        return $lender;
    }
}
