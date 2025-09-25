<?php

namespace App\Actions\Contracts\Orders\TraderOrders\Fees;

interface DeductBalanceForCompletedOrder extends FeeActionInterface
{
    // This interface now inherits the handle method from FeeActionInterface
    // Specific implementations can add additional methods if needed
}
