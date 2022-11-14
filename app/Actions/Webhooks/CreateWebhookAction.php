<?php

namespace App\Actions\Webhooks;

use App\Actions\Contracts\CreateWebhook;
use App\Models\Webhook;
use Illuminate\Support\Arr;

class CreateWebhookAction implements CreateWebhook
{
    /**
     * @return mixed
     */
    public function handle($data): Webhook
    {
        return Webhook::create(Arr::only(
            $data,
            [
                'url',
                'type',
            ]
        ));
    }
}
