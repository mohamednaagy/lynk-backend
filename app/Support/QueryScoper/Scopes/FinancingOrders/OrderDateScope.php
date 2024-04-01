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
                'creation_start_date' => ['nullable', 'date', 'before_or_equal:creation_end_date', 'before_or_equal:'.Carbon::now()->toDateString()],
                'creation_end_date' => ['nullable', 'date', 'after_or_equal:creation_start_date', 'before_or_equal:'.Carbon::now()->toDateString()],
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
        $startDate = Carbon::parse($data['creation_start_date'])->startOfDay();
        $endDate = Carbon::parse($data['creation_end_date'])->endOfDay();

        return $builder->whereBetween('created_at', [$startDate, $endDate]);
    }
}
