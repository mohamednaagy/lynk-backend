<?php

namespace App\Actions\Orders\Webhooks\Product;

use App\Models\TraderOrder;

interface ProductInterface
{
    public function map(TraderOrder $traderOrder): array;
}
