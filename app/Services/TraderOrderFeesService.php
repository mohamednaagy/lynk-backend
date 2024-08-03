<?php

namespace App\Services;

use App\Actions\Contracts\Orders\TraderOrders\Fees\DeductBalanceForCompletedOrder;
use App\Actions\Contracts\Orders\TraderOrders\Fees\DeductBalanceForNewOrder;
use App\Enums\Trader;
use App\Enums\TraderOrderStatus;

class TraderOrderFeesService
{
    /**
     * Mapping of actions based on provider and status.
     *
     * @var array
     */
    protected $actions = [
        Trader::Bursam => [
            TraderOrderStatus::InProgress => DeductBalanceForNewOrder::class,
        ],
        Trader::Dmcc => [
            TraderOrderStatus::InProgress => DeductBalanceForNewOrder::class,
        ],
        Trader::FakeDmcc => [
            TraderOrderStatus::InProgress => DeductBalanceForNewOrder::class,
        ],
        Trader::Lynk => [
            TraderOrderStatus::Completed => DeductBalanceForCompletedOrder::class,
        ],
    ];

    /**
     * Get the action class for the given provider and status.
     *
     * @param string $provider
     * @param string $status
     * @return mixed|null
     */
    public function getAction(string $provider, string $status)
    {
        return isset($this->actions[$provider][$status]) ? app($this->actions[$provider][$status]) : null;
    }
}
