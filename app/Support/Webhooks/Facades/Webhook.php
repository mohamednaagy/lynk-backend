<?php

namespace App\Support\Webhooks\Facades;

use Illuminate\Support\Facades\Facade;

class Webhook extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     *
     * @throws RuntimeException
     */
    protected static function getFacadeAccessor(): string
    {
        return 'webhook';
    }
}
