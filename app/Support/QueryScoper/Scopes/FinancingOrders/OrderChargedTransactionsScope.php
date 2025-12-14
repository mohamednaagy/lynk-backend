<?php

namespace App\Support\QueryScoper\Scopes\FinancingOrders;

use App\Support\QueryScoper\QueryScoper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OrderChargedTransactionsScope extends QueryScoper
{
    /**
     * Prepare data for validation
     *
     * @return array
     */
    public function prepareData()
    {
        return [
            'charged_transactions' => Request::query('charged_transactions'),
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
                'charged_transactions' => ['required', Rule::in('1', '0')],
            ]
        );
    }

    /**
     * Prepare builder
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  array  $data
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function prepareBuilder($builder, $data)
    {
        return match ($data['charged_transactions']) {
            '1' => $builder->where('charged_trader_orders_count', '>', 0),
            '0' => $builder->where('charged_trader_orders_count', '=', 0),
            default => $builder,
        };
    }
}
