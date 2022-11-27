<?php

// __REVIEW__ need to change file path "app/Support/QueryScoper/Scopes/FinancingOrders/OrderNeedActionScope.php"

namespace App\Support\QueryScoper\Scopes\Lender\Orders;

use App\Enums\FinancingOrderStatus;
use App\Support\QueryScoper\QueryScoper;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
                'need_action' => ['required', Rule::in('1', '0')],
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
        // __REVIEW__ we need to update statuses list to be:
        // PendingApproval
        // ContractSigned
        // WaitingClientWakala
        // MurabahaSaleCompleted
        // Rejected
        if ($data['need_action'] === '1') {
            return $builder->whereIn(
                'status',
                [
                    FinancingOrderStatus::PendingApproval,
                    FinancingOrderStatus::Approved,
                    FinancingOrderStatus::Rejected,
                ]
            );
        }

        return $builder;
    }
}
