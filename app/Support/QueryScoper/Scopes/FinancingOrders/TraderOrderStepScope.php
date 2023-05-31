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
        $allHistories = $this->getHistoriesOfAllTradersForStep($data['step']);

        return $builder->whereHas('activeTraderOrder.traderHistories', function ($query) use ($allHistories) {
            $query->whereIn('action', $allHistories);
        });
    }

    public function getHistoriesOfAllTradersForStep($step)
    {
        $allHistories = [];

        foreach ($this->getProviders() as $provider) {
            $traderHistories = $this->getHistoriesOfProvider($provider, $step);

            $allHistories = array_merge($allHistories, $traderHistories);
        }

        return $allHistories;
    }

    protected function getProviders()
    {
        return array_keys(config('trader.providers'));
    }

    protected function getHistoriesOfProvider($provider, $step)
    {
        $traderHistories = [];

        foreach ($this->getProviderVersions($provider) as $version) {
            $versionHistories = (new StepHistoriesDictionary($provider, $version))
                ->getStepOf($step)
                ?->histories;

            if ($versionHistories === null) {
                continue;
            }

            $traderHistories = array_merge($traderHistories, $versionHistories);
        }

        return $traderHistories;
    }

    protected function getProviderVersions($provider)
    {
        return array_keys(config('murabha-steps.'.$provider.'-versions'));
    }
}
