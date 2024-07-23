<?php

namespace App\Actions\Orders\Webhooks\Product;

use App\Enums\Trader;

class ProductFactory
{
    public static function create($type)
    {
        switch ($type) {
            case Trader::Lynk:
                return new LynkProduct();
            case Trader::Bursam:
                return new BursamProduct();
            case Trader::Dmcc:
                return new DmccProduct();
            case Trader::FakeDmcc:
                return new FakeProduct();
            default:
                throw new \Exception("Product type $type is not supported.");
        }
    }
}
