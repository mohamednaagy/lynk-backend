<?php

namespace App\Actions\Traders;

use App\Actions\Contracts\Traders\ShowTrader;
use App\Models\Company;

class ShowTraderAction implements ShowTrader
{
    /**
     * @param  \App\Models\Company  $company
     * @return mixed
     */
    public function handle(Company $trader)
    {
        return $trader->loadSum([
            'orders' => function ($query) use ($trader) {
                $query->whereHas('traderOrders', function ($query) use ($trader) {
                    $query->inProgressOrCompletedTraderOrder()
                        ->whereHas('order', function ($query) use ($trader) {
                            $query->where('company_id', $trader->id);
                        });
                });
            },
        ], 'amount')
            ->loadCount([
                'orders' => function ($query) use ($trader) {
                    return $query->whereHas(
                        'traderOrders.order',
                        function ($query) use ($trader) {
                            $query->where('company_id', $trader->id);
                        }
                    );
                },
            ]);
    }
}
