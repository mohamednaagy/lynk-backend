<?php

namespace App\Support\Traders\Drivers;

use App\Support\DMCC\DMCCService;

class DmccDriver
{
    public function createTTI(): string
    {
        return (new DMCCService())->getTTI('', '', '');
    }

    public function createPTP(): string
    {
        return (new DMCCService())->respondPTPService('');
    }

    public function createMPO(): string
    {
        return (new DMCCService())->issueMurabahaPurchaseOffer('');
    }

    public function processNotifications(): string
    {
        return 'processNotifications';
    }
}
