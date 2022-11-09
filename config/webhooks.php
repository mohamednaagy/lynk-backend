<?php

use App\Enums\WebhookType;

return [
    'limits' => [
        WebhookType::OrderUpdates => 10,
    ],
];
