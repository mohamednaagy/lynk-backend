<?php

namespace App\Transformers;

use App\Models\Webhook;
use League\Fractal\TransformerAbstract;

class WebhookTransformer extends TransformerAbstract
{
    protected array $defaultIncludes = [
        'webhook_type',
    ];

    public function transform(Webhook $webhook)
    {
        return [
            'id' => $webhook->id,
            'webhook_url' => $webhook->webhook_url,
            'webhook_secret_key' => $webhook->webhook_secret_key,
        ];
    }

        public function includeWebhookType(Webhook $webhook)
        {
            return $this->primitive([
                'description' => $webhook->webhook_type->description,
                'value' => $webhook->webhook_type->value,
            ]);
        }
}
