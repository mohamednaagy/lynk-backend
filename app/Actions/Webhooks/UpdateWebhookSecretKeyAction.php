<?php

namespace App\Actions\Webhooks;

use App\Actions\Contracts\Webhooks\GenerateWebhookSecretKey;
use App\Actions\Contracts\Webhooks\UpdateWebhookSecretKey;
use App\Models\Company;

class UpdateWebhookSecretKeyAction implements UpdateWebhookSecretKey
{
    public function __construct(protected GenerateWebhookSecretKey $generateWebhookSecretKey)
    {
    }

    public function handle(Company $company): Company
    {
        $company->update(
            [
                'webhook_secret_key' => $this->generateWebhookSecretKey->handle(),
            ]
        );

        return $company;
    }
}
