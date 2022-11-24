<?php

namespace App\Actions\Webhooks;

use App\Actions\Contracts\Webhooks\UpdateWebhookSecretKey;
use App\Models\Company;

class UpdateWebhookSecretKeyAction implements UpdateWebhookSecretKey
{
    public function handle(Company $company): Company
    {
        $company->update(
            [
                'webhook_secret_key' => 'secret-key:'.$company->unique_name.':'.now()->toString(),
            ]
        );

        return $company;
    }
}
