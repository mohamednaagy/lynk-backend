<?php

namespace App\Transformers;

use App\Actions\Contracts\Companies\CalculateVatAmount;
use App\Models\TieredPricing;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class OrderCostTierTransformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include
     */
    protected array $defaultIncludes = [
        //
    ];

    /**
     * List of resources possible to include
     */
    protected array $availableIncludes = [
        'id',
        'order_value_start',
        'order_value_end',
        'fee_type',
        'order_cost_without_vat',
        'order_cost_with_vat',
        'proration_amount',
    ];

    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform()
    {
        return [
            //
        ];
    }

    public function includeId(TieredPricing $tieredPricing): Primitive
    {
        return $this->primitive($tieredPricing->id);
    }

    public function includeOrderValueStart(TieredPricing $tieredPricing): Primitive
    {
        return $this->primitive($tieredPricing->order_value_start);
    }

    public function includeOrderValueEnd(TieredPricing $tieredPricing): Primitive
    {
        return $this->primitive($tieredPricing->order_value_end);
    }

    public function includeFeeType(TieredPricing $tieredPricing): Primitive
    {
        return $this->primitive($tieredPricing->fee_type);
    }

    public function includeOrderCostWithoutVat(TieredPricing $tieredPricing): Primitive
    {
        return $this->primitive($tieredPricing->order_cost_without_vat);
    }

    public function includeOrderCostWithVat(TieredPricing $tieredPricing): Primitive
    {
        $orderCostWithoutVat = $tieredPricing->order_cost_without_vat;

        [$vatAmount, $vatRate] = app(CalculateVatAmount::class)
            ->setAmount($orderCostWithoutVat)
            ->setIsVatIncludedInAmount(false)
            ->handle();

        return $this->primitive($orderCostWithoutVat->add($vatAmount));
    }

    public function includeProrationAmount(TieredPricing $tieredPricing): Primitive
    {
        return $this->primitive($tieredPricing->proration_amount);
    }
}
