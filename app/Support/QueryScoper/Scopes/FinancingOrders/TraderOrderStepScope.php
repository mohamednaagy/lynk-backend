<?php

namespace App\Support\QueryScoper\Scopes\FinancingOrders;

use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use App\Support\QueryScoper\QueryScoper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class TraderOrderStepScope extends QueryScoper
{
    /**
     * Prepare data for vailation
     *
     * @return array
     */
    public function prepareData()
    {
        return [
            'stage' => Request::query('stage'),
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
                'stage' => ['nullable', 'string'],
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
        if (isset($data['stage'])) {
            return $builder->whereHas('activeTraderOrder', function ($query) use ($data) {
                $query->whereHas('traderHistories', function ($query) use ($data) {
                    $histories = (new StepHistoriesDictionary())
                        ->getStepOf($data['stage'])
                        ?->histories;

                    $query->whereIn('action', $histories ?? []);
                });
            });
        }

        return $builder;
    }
}
