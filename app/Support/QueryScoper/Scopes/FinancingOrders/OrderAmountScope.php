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
            'amount_lte' => Request::query('amount_lte'),
            'amount_gte' => Request::query('amount_gte'),
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
                'amount_lte' => ['nullable', 'numeric', isset($data['amount_gte']) ? 'gte:amount_gte' : ''],
                'amount_gte' => ['nullable', 'numeric', isset($data['amount_lte']) ? 'lte:amount_lte' : ''],
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
        $amountLTE = isset($data['amount_lte'])
            ? Money::parseByDecimal($data['amount_lte'], Money::getDefaultCurrency())
            : null;
        $amountGTE = isset($data['amount_gte'])
            ? Money::parseByDecimal($data['amount_gte'], Money::getDefaultCurrency())
            : null;

        if ($amountLTE && $amountGTE) {
//            dd($amountGTE, $amountLTE);
            return $builder->whereBetween('amount', [$amountGTE->getAmount(), $amountLTE->getAmount()]);
        }

        if ($amountLTE) {
            return $builder->where('amount', '<=', $amountLTE->getAmount());
        }

        if ($amountGTE) {
            return $builder->where('amount', '>=', $amountGTE->getAmount());
        }

        return $builder;
    }
}
