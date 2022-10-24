<?php

namespace App\Support\Traders\Drivers;

class DmccDriver
{
    public function createTTI(): string
    {
        return 'createTTI';
    }

    public function createPTP(): string
    {
        return 'createPTP';
    }

    public function createMPO(): string
    {
        return 'createMPO';
    }

    public function processNotifications(): string
    {
        return 'processNotifications';
    }
}
