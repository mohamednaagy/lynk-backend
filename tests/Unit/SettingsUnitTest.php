<?php

namespace Tests\Unit;

use App\Actions\GetSettingsAreaAction;
use App\Actions\GetSettingsClassInstanceAction;
use App\Actions\ListSettingsAction;
use App\Actions\UpdateSettingsAction;
use App\Enums\Area;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_get_all_settings()
    {
        $instanceAction = new GetSettingsClassInstanceAction();
        $list = new ListSettingsAction($instanceAction);

        $this->assertIsArray($list->handle());
    }

    public function test_update_otp_enabled_settings()
    {
        $getSettingAreaAction = new GetSettingsAreaAction();
        $updateSettingsAction = new UpdateSettingsAction($getSettingAreaAction);

        $data = [
            'area' => Area::SuperAdmin,
            'otp_driver' => 'email',
            'otp_enabled' => true,
        ];
        $updateSettingsAction->handle($data);

        $getSettingsAction = new GetSettingsClassInstanceAction();
        $superAdminSettings = $getSettingsAction->handle(Area::SuperAdmin);

        $this->assertEquals($data['otp_enabled'], $superAdminSettings->otp_enabled);
    }

    public function test_update_otp_driver_settings()
    {
        $getSettingAreaAction = new GetSettingsAreaAction();
        $updateSettingsAction = new UpdateSettingsAction($getSettingAreaAction);

        $data = [
            'area' => Area::SuperAdmin,
            'otp_driver' => 'email',
            'otp_enabled' => true,
        ];
        $updateSettingsAction->handle($data);

        $getSettingsAction = new GetSettingsClassInstanceAction();
        $generalSettings = $getSettingsAction->handle(Area::SuperAdmin);

        $this->assertEquals($data['otp_driver'], $generalSettings->otp_driver);
    }
}
