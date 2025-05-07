<?php

namespace App\Transformers;

use App\Models\TraderProduct;
use League\Fractal\TransformerAbstract;

class TraderProductTransformer extends TransformerAbstract
{
    public function transform(TraderProduct $traderProduct)
    {
        return [
            'id' => $traderProduct->id,
            'name' => $traderProduct->getTranslation('name', app()->getLocale()),
            'code' => $traderProduct->code,
        ];
    }
}
