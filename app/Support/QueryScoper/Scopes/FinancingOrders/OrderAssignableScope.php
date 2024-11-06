<?php

namespace App\Support\QueryScoper\Scopes\FinancingOrders;

use App\Support\QueryScoper\QueryScoper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class OrderAssignableScope extends QueryScoper
{

    private const NOT_AASSIGNED_FILTER_VALUE = '-1';
    /**
     * Prepare builder
     *
     * @param  Builder  $builder
     * @param  array  $data
     * @return Builder
     */
    public function prepareBuilder($builder, $data): Builder
    {
        if (!empty($data['assignable_id']) && in_array(self::NOT_AASSIGNED_FILTER_VALUE, $data['assignable_id'], true))
            return $builder->whereIn('assignable_id', $data['assignable_id'])->orWhereNull('assignable_id');

        if (!empty($data['assignable_id']) && !in_array(self::NOT_AASSIGNED_FILTER_VALUE, $data['assignable_id'], true))
            return $builder->whereIn('assignable_id', $data['assignable_id']);

        return $builder;
    }

    /**
     * Prepare data
     *
     * @return array
     */
    public function prepareData(): array
    {
        return [
            'assignable_id' => collect(Request::query('assignable_id'))->pluck('value')->toArray()
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
            'assignable_id.*' => ['required', 'integer', 'in:-1,' . implode(',', $this->getValidUserIds())],
        ]);
    }

    /**
     * Get valid user IDs for validation
     *
     * @return array
     */
    protected function getValidUserIds(): array
    {
        return \App\Models\User::pluck('id')->toArray();
    }
}
