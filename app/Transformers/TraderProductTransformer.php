<?php

namespace App\Transformers;

use App\Models\TraderProduct;
use League\Fractal\TransformerAbstract;

class TraderProductTransformer extends TransformerAbstract
{
    public function transform(TraderProduct $traderProduct)
    {
        return $traderProduct->toArray();
    }
}
