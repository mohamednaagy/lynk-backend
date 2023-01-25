<?php

namespace App\Support\Webhooks\Facades;

use App\Models\Company;
use Illuminate\Support\Facades\Facade;
use RuntimeException;

/**
 * @method static void fire(Company $company, string $webhookType, array $payload)
 */
class WebhookEvent extends Facade
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
        return 'webhookEvent';
    }
}
