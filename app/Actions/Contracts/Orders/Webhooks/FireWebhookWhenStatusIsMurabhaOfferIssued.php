<?php

namespace App\Actions\Contracts\Orders\Webhooks;

use App\Models\FinancingOrder;

interface FireWebhookWhenStatusIsMurabhaOfferIssued
{
    public function handle(FinancingOrder $financingOrder): void;
}
