<?php

namespace App\Services;

use App\Actions\Contracts\Orders\TraderOrders\Fees\DeductBalanceForCompletedOrder;
use App\Actions\Contracts\Orders\TraderOrders\Fees\DeductBalanceForDeliveryConfirmed;
use App\Actions\Contracts\Orders\TraderOrders\Fees\DeductBalanceForNewOrder;
use App\Enums\FinancingOrderHistory;
use App\Enums\Trader;
class TraderOrderFeesService
{
    /**
     * Mapping of actions based on provider and status.
     *
     * @var array
     */
    protected $actions = [
        Trader::Bursam => [
            FinancingOrderHistory::GetTtiId => DeductBalanceForNewOrder::class,
        ],
        Trader::Dmcc => [
            FinancingOrderHistory::GetTtiId => DeductBalanceForNewOrder::class,
        ],
        Trader::FakeDmcc => [
            FinancingOrderHistory::GetTtiId => DeductBalanceForNewOrder::class,
        ],
        Trader::Lynk => [
            FinancingOrderHistory::MurabahaSaleCompleted => DeductBalanceForCompletedOrder::class,
            FinancingOrderHistory::DeliveryConfirmed => DeductBalanceForDeliveryConfirmed::class,
        ],
    ];

    /**
     * Get the action class for the given provider and status.
     *
     * @return mixed|null
     */
    public function getAction(string $provider, string $status)
    {
        return isset($this->actions[$provider][$status]) ? app($this->actions[$provider][$status]) : null;
    }
}
