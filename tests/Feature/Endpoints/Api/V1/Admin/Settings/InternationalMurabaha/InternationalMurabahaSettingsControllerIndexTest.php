<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Settings\InternationalMurabaha;

use App\Enums\Role;
use App\Enums\Trader;
use App\Enums\TraderProductStatus;
use App\Models\TraderProduct;
use App\Models\User;
use App\Settings\Classes\InternationalMurabahaSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class InternationalMurabahaSettingsControllerIndexTest extends TestCase
{
    use AssertsAccessByRoleAndArea, RefreshDatabase;

    private static User $userAdmin;

    private static User $userManager;

    private static TraderProduct $traderProduct;

    private string $endpoint = 'api/v1/admin/settings/international-murabaha';

    protected function setUp(): void
    {
        parent::setUp();

        self::$userAdmin = $this->createSuperAdminUser();
        self::$userManager = $this->createSuperAdminUser(Role::Manager);

        // Create a trader product
        self::$traderProduct = TraderProduct::factory()->create([
            'name' => [
                'en' => 'Test Product EN',
                'ar' => 'Test Product AR',
            ],
            'status' => TraderProductStatus::Enabled,
            'provider' => Trader::Bursam,
            'order' => 1,
        ]);

        // Initialize settings
        $settings = new InternationalMurabahaSetting;
        $settings->bursam_default_preferred_commodity_type = self::$traderProduct->id;
        $settings->save();
    }

    public function test_un_auth_user_cant_access_international_murabaha_settings(): void
    {
        $this->getJson($this->endpoint)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_admin_can_access_international_murabaha_settings_successfully(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson($this->endpoint)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'bursam_default_preferred_commodity_type',
                ],
            ])
            ->assertJsonPath('data.bursam_default_preferred_commodity_type', self::$traderProduct->id);
    }

    public function test_manager_without_permissions_cant_access_international_murabaha_settings(): void
    {
        $this->actingAs(self::$userManager)
            ->getJson($this->endpoint)
            ->assertForbidden();
    }
}
