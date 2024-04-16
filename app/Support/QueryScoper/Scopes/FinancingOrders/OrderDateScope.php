<?php

namespace App\Support\QueryScoper\Scopes\FinancingOrders;

use App\Support\QueryScoper\QueryScoper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class OrderDateScope extends QueryScoper
{
    /**
     * Prepare data for vailation
     *
     * @return array
     */
    public function prepareData()
    {
        return [
            'creation_start_date' => Request::query('creation_start_date'),
            'creation_end_date' => Request::query('creation_end_date'),
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
                'creation_start_date' => ['nullable', 'date'],
                'creation_end_date' => ['nullable', 'date'],
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
        $startDate = isset($data['creation_start_date']) ? Carbon::parse($data['creation_start_date']) : null;
        $endDate = isset($data['creation_end_date']) ? Carbon::parse($data['creation_end_date'])->endOfDay() : null;

        if ($startDate && $endDate) {
            return $builder->whereBetween('created_at', [$startDate, $endDate]);

        }

        if ($startDate) {
            return $builder->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            return $builder->where('created_at', '<=', $endDate);
        }

        return $builder;

    }
}
