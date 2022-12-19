<?php

namespace App\Actions\Contracts\Webhooks;

use App\Models\Company;

interface UpdateWebhookSecretKey
{
    public function handle(Company $company): Company;
}
