<?php
namespace App\Services;

use App\Actions\Contracts\Orders\DeductBalanceForCompletedOrder;
use App\Actions\Contracts\Orders\DeductBalanceForNewOrder;
use App\Enums\Trader;
use App\Enums\TraderOrderStatus;

class TraderOrderStatusService
{
    /**
     * Mapping of actions based on provider and status.
     *
     * @var array
     */
    protected $actions = [
        Trader::Bursam => [
            TraderOrderStatus::InProgress => DeductBalanceForNewOrder::class,
            TraderOrderStatus::Completed => null,
        ],
        Trader::Dmcc => [
            TraderOrderStatus::InProgress => DeductBalanceForNewOrder::class,
            TraderOrderStatus::Completed => null,
        ],
        Trader::FakeDmcc => [
            TraderOrderStatus::InProgress => DeductBalanceForNewOrder::class,
            TraderOrderStatus::Completed => null,
        ],
        Trader::Lynk => [
            TraderOrderStatus::InProgress => null,
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
        if (!isset($this->actions[$provider])) {
            return null;
        }

        if (!isset($this->actions[$provider][$status])) {
            return null;
        }

        $action = $this->actions[$provider][$status];

        if ($action) {
            return app($action); 
        }

        return null;
    }

}
