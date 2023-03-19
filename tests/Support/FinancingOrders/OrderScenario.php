<?php

namespace Tests\Support\FinancingOrders;

use App\Enums\FinancingOrderStatus;
use App\Models\User;
use Carbon\Carbon;

class OrderScenario
{
    public static function approved(User $approver, null|Carbon $approvedAt)
    {
        return (new OrderCreator)->approvedAt($approver, $approvedAt ?? now())
            ->status(FinancingOrderStatus::Approved)
            ->commit();
    }

    public static function rejected(string $rejectionReason = '')
    {
        return (new OrderCreator)->status(FinancingOrderStatus::Rejected)
            ->statusReason($rejectionReason)
            ->commit();
    }

    public static function cancelled()
    {
        return (new OrderCreator)->status(FinancingOrderStatus::Cancelled)
            ->commit();
    }

    public static function completed()
    {
        return (new OrderCreator)->status(FinancingOrderStatus::Completed)
            ->commit();
    }

    public static function inProgress()
    {
        $order = (new OrderCreator)->status(FinancingOrderStatus::InProgress)
            ->commit();

        return InProgressOrder::startFrom($order);
    }
}
