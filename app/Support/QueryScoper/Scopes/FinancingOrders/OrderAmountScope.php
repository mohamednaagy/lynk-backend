<?php

namespace App\Support\QueryScoper\Scopes\FinancingOrders;

use App\Support\Money\Money;
use App\Support\QueryScoper\QueryScoper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class OrderAmountScope extends QueryScoper
{
    /**
     * Prepare data for vailation
     *
     * @return array
     */
    public function prepareData()
    {
        return [
            'amount' => Request::query('amount'),
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
                'amount' => ['required', 'numeric'],
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
        if ($amount = $data['amount']) {
            $amount = Money::parseByDecimal($amount, Money::getDefaultCurrency());
            $operator = '>=';
            if ($amount->isNegative()) {
                $amount = $amount->multiply(-1);
                $operator = '<=';
            }

            return $builder->where(function (Builder $builder) use ($operator, $amount) {
                $builder->where('amount', $operator, $amount->getAmount());
            });
        }

        return $builder;
    }
}
