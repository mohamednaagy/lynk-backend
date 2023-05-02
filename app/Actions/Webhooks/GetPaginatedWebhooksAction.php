<?php

namespace App\Actions\Webhooks;

use App\Actions\Contracts\Webhooks\GetPaginatedWebhooks;
use App\Models\Webhook;
use Illuminate\Database\Eloquent\Collection;

class GetPaginatedWebhooksAction implements GetPaginatedWebhooks
{
    public function handle(): Collection
    {
        return Webhook::query()
            ->latest()
            ->get();
    }
}
