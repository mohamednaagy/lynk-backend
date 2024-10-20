<?php

namespace App\Actions\Contracts\Orders;

interface LocalMarketWebhook
{
    public function handle(): void;
}
