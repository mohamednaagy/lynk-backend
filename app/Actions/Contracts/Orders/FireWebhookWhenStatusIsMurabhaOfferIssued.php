<?php

namespace App\Actions\Contracts\Orders;

use App\Models\FinancingOrder;

interface FireWebhookWhenStatusIsMurabhaOfferIssued
{
    public function handle(FinancingOrder $financingOrder): void;
}
