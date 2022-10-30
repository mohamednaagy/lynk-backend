<?php

namespace App\Support\QueryScoper\Scopes\Lender\Orders;

use App\Enums\FinancingOrderStatus;
use App\Support\QueryScoper\QueryScoper;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class OrderNeedActionScope extends QueryScoper
{
    /**
     * Prepare data for vailation
     *
     * @return array
     */
    public function prepareData()
    {
        return [
            'need_action' => Request::query('need_action'),
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
                'need_action' => ['required', 'boolean'],
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
        return $builder->whereIn(
            'status',
            [
                FinancingOrderStatus::PendingApproval, FinancingOrderStatus::InProgress, FinancingOrderStatus::Rejected,
            ]
        );
    }
}
