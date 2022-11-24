<?php

namespace App\Transformers;

use App\Models\Webhook;
use League\Fractal\TransformerAbstract;

class WebhookTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'id',
        'url',
        'type',
    ];

    public function transform(Webhook $webhook)
    {
        return [];
    }

    public function includeId(Webhook $webhook)
    {
        return $this->primitive($webhook->id);
    }

    public function includeType(Webhook $webhook)
    {
        return $this->primitive([
            'description' => $webhook->type->description,
            'value' => $webhook->type->value,
        ]);
    }

    public function includeUrl(Webhook $webhook)
    {
        return $this->primitive($webhook->url);
    }
}
