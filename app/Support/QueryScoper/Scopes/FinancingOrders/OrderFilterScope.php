<?php

namespace App\Support\QueryScoper\Scopes\FinancingOrders;

use App\Support\QueryScoper\QueryScoper;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OrderFilterScope extends QueryScoper
{
    const FILTER_ACTIVE = 'active';

    const FILTER_NEED_ACTION = 'need_action';

    const FILTER_COMPLETED = 'completed';

    const FILTER_CANCELLED = 'cancelled';

    const FILTER_REJECTED = 'rejected';

    const FILTERS = [
        self::FILTER_ACTIVE,
        self::FILTER_NEED_ACTION,
        self::FILTER_COMPLETED,
        self::FILTER_CANCELLED,
        self::FILTER_REJECTED,
    ];

    /**
     * Prepare data for vailation
     *
     * @return array
     */
    public function prepareData()
    {
        return [
            'filter' => Request::query('filter'),
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
                'filter' => ['required', Rule::in(self::FILTERS)],
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
        return match ($data['filter']) {
            self::FILTER_ACTIVE => $builder->active(),
            self::FILTER_NEED_ACTION => $builder->requireAction(),
            self::FILTER_COMPLETED => $builder->completed(),
            self::FILTER_CANCELLED => $builder->cancelled(),
            self::FILTER_REJECTED => $builder->rejected(),
            default => $builder,
        };
    }
}
