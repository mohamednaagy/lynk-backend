<?php

namespace App\Support\Traders\Contracts;

use App\Models\FinancingOrder;

interface TraderInterface
{
    public function acceptAgreement();

    public function getTti(FinancingOrder $financingOrder);

    public function respondPtp(string $ttiId);

    public function issueMurabaha(string $ttiId);

    public function fetchNotification(string $type);
}
