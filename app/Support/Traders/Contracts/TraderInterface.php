<?php

namespace App\Support\Traders\Contracts;

use App\Models\FinancingOrder;

interface TraderInterface
{
    public function acceptAgreement();

    public function getTTI(FinancingOrder $financingOrder);

    public function respondPTP(string $ttiId);

    public function issueMurabaha(string $ttiId);

    public function fetchNotification(string $type);
}
