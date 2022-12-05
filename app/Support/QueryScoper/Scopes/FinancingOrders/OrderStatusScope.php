<?php

namespace App\Support\QueryScoper\Scopes\FinancingOrders;

use App\Enums\FinancingOrderStatus;
use App\Support\QueryScoper\QueryScoper;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class OrderStatusScope extends QueryScoper
{
    /**
     * Prepare data for vailation
     *
     * @return array
     */
    public function prepareData()
    {
        $status = Request::query('status');

        return [
            'status' => is_array($status)
                ? $status
                : explode(',', Request::query('status')),
        ];
    }

    /**
     * Get the validator
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validator($data)
    {
        return Validator::make(
            $data,
            [
                'status' => ['required', 'array', new EnumValue(FinancingOrderStatus::class)],
            ]
        );
    }

    /**
     * Prepare builder
     *
     * @param  Builder  $builder
     * @param  array  $data
     * @return Builder
     */
    public function prepareBuilder($builder, $data)
    {
        return $builder->whereIn('status', $data['status']);
    }
}
