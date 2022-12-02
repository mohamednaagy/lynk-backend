<?php

namespace App\Support\QueryScoper\Scopes\FinancingOrders;

use App\Support\QueryScoper\QueryScoper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
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
        return [
            'status' => Request::query('status'),
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
                'status' => ['required'],
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
        $statuses = Arr::wrap($data['status']);
        if (count($statuses)) {
            return $builder->where(function (Builder $builder) use ($statuses) {
                $builder->whereIn('status', $statuses);
            });
        }

        return $builder;
    }
}
