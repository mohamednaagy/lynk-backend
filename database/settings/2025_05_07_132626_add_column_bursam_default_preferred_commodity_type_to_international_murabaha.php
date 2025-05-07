<?php

use App\Models\TraderProduct;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('international_murabaha.bursam_default_preferred_commodity_type', $this->getDefaultBursamDefaultPreferredCommodityType());
    }

    private function getDefaultBursamDefaultPreferredCommodityType(): int
    {
        $code = env('APP_ENV') === 'production' ? 'PR-B MSIA14' : 'PR-B-MSIA14';

        return TraderProduct::where('code', $code)->first()->id;
    }
};
