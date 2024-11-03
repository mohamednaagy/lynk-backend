<?php

namespace App\Support\QueryScoper\Scopes\FinancingOrders;

use App\Support\Money\Money;
use App\Support\QueryScoper\QueryScoper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class OrderAssignableScope extends QueryScoper
{
    /**
     * Prepare builder
     *
     * @param  Builder  $builder
     * @param  array  $data
     * @return Builder
     */
    public function prepareBuilder($builder, $data): Builder
    {
        if ($data['assignable_id']) {
            $builder->where('assignable_id', $data['assignable_id']);
        }
        return $builder;
    }

    /**
     * Prepare data
     *
     * @return array
     */
    /**
     * Get data from request query and prepare them for the scope
     *
     * @return array
     */
    public function prepareData(): array
    {
        return [
            'assignable_id' => collect(Request::query('assignable_id'))->pluck('id')->toArray()
        ];
    }

    /**
     * Get the validator
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validator($data): \Illuminate\Contracts\Validation\Validator
    {

        return Validator::make($data, [
            'assignable_id' => ['nullable', 'array'],
            'assignable_id.*' => ['required', 'integer', 'exists:users,id'],
        ]);
    }
}
