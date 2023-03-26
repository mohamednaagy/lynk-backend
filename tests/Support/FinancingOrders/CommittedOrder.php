<?php

namespace Tests\Support\FinancingOrders;

use App\Models\FinancingOrder;
use Tests\Traits\InteractsWithCompany;

class CommittedOrder extends OrderAttributesSetter
{
    use InteractsWithCompany;

    protected array $attributes = [];

    protected function __construct(protected FinancingOrder $financingOrder)
    {
    }

    public static function of(FinancingOrder $financingOrder)
    {
        return new static($financingOrder);
    }

    public function commit()
    {
        $this->financingOrder->update($this->attributes);

        $this->attributes = [];
    }

    public function model()
    {
        return $this->financingOrder;
    }

    public function __get($attribute)
    {
        return $this->financingOrder->getAttribute($attribute);
    }
}
