<?php

namespace App\Settings\Classes;

use Illuminate\Support\Facades\DB;
use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $otp_driver;

    public int $trader_order_timeout;

    public array $order_responsible_admins;

    public static function group(): string
    {
        return 'general';
    }

    public function getOrderResponsibleAdminsWithoutCache(): array
    {
        return json_decode(DB::table('settings')
            ->where('name', 'order_responsible_admins')
            ->value('payload'), true) ?: [];
    }
}
