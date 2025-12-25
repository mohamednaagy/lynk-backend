<?php

namespace App\Actions\Contracts\Webhooks;

use App\Models\Lender;

interface UpdateWebhookSecretKey
{
    public function handle(Lender $lender): Lender;
}
