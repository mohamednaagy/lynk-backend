<?php

namespace App\Support\QueryScoper\Scopes\FinancingOrders;

use App\Support\QueryScoper\QueryScoper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OrderSortScope extends QueryScoper
{
    /**
     * Prepare data for vailation
     *
     * @return array
     */
    public function prepareData()
    {
        return [
            'sort' => Request::query('sort'),
            'direction' => Request::query('direction'),
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
                'sort' => ['required', Rule::in('created_at', 'amount')],
                'direction' => ['nullable', Rule::in('asc', 'desc')],
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
        if ($sort = $data['sort']) {
            return $builder->orderBy($sort, $data['direction'] ?? 'asc');
        }

        return $builder;
    }
}
