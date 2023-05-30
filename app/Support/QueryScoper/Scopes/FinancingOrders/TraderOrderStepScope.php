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
            'step' => Request::query('step'),
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
                'step' => ['required', 'string'],
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
        $allHistories = [];

        foreach (config('trader.providers') as $trader => $value) {
            foreach (config('murabha-steps.'.$trader.'-versions') as $version => $versionValue) {
                $traderHistories = (new StepHistoriesDictionary($trader, $version))
                    ->getStepOf($data['step'])
                    ?->histories;
            }

            $allHistories = array_merge($allHistories, $traderHistories);
        }

        if (isset($data['step'])) {
            return $builder->whereHas('activeTraderOrder', function ($query) use ($allHistories) {
                $query->whereHas('traderHistories', function ($query) use ($allHistories) {
                    $query->whereIn('action', $allHistories);
                });
            });
        }

        return $builder;
    }
}
