<?php

namespace App\Actions\Contracts\Orders;

interface LocalMarketWebhook
{
    public function handle(
        array $data,
    ): void;
}
