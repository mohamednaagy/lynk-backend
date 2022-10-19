<?php

namespace App\Transformers;

use App\Models\FinancingOrder;
use League\Fractal\TransformerAbstract;

class FinancingOrderTransformer extends TransformerAbstract
{
    protected array $defaultIncludes = [
        'id',
        'status',
        'company_id',
        'reference_number',
        'national_id',
        'amount',
        'selling_price',
        'contract',
        'power_of_attorney',
    ];

    public function transform(FinancingOrder $financingOrder)
    {
        return [];
    }

    public function includeId(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->id);
    }

    public function includeStatus(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->status->description);
    }

    public function includeCompanyId(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->company_id);
    }

    public function includeReferenceNumber(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->reference_number);
    }

    public function includeNationalId(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->national_id);
    }

    public function includeAmount(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->amount);
    }

    public function includeSellingPrice(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->selling_price);
    }

    public function includeContract(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->selling_price);
    }

    public function includePowerOfAttorney(FinancingOrder $financingOrder)
    {
        return $this->primitive($financingOrder->power_of_attorney);
    }
}
