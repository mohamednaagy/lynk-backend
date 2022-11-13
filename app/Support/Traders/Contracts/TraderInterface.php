<?php

namespace App\Support\Traders\Contracts;

use App\Models\FinancingOrder;

interface TraderInterface
{
    public function acceptAgreement();

    public function getTti(FinancingOrder $financingOrder);

    public function fetchNotification(string $type);
}
