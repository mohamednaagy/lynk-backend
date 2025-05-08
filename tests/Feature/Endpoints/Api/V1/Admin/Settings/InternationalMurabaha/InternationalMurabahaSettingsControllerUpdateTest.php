<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Settings\InternationalMurabaha;

use App\Enums\Role;
use App\Models\TraderProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class InternationalMurabahaSettingsControllerUpdateTest extends TestCase
{
    use AssertsAccessByRoleAndArea, RefreshDatabase;

    private static User $userAdmin;

    private static User $userManager;

    private static TraderProduct $traderProduct;

    private string $endpoint = 'api/v1/admin/settings/international-murabaha';

    public function setUp(): void
    {
        parent::setUp();

        self::$userAdmin = $this->createSuperAdminUser();
        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        self::$traderProduct = TraderProduct::factory()->create();
    }

    public function test_un_auth_user_cant_update_international_murabaha_settings(): void
    {
        $this->putJson($this->endpoint, [
            'bursam_default_preferred_commodity_type' => self::$traderProduct->id,
        ])
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_admin_can_update_international_murabaha_settings_successfully(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson($this->endpoint, [
                'bursam_default_preferred_commodity_type' => self::$traderProduct->id,
            ])
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'bursam_default_preferred_commodity_type',
                ],
            ])
            ->assertJsonPath('data.bursam_default_preferred_commodity_type', self::$traderProduct->id);
    }

    public function test_manager_without_permissions_cant_update_international_murabaha_settings(): void
    {
        $this->actingAs(self::$userManager)
            ->putJson($this->endpoint, [
                'bursam_default_preferred_commodity_type' => self::$traderProduct->id,
            ])
            ->assertForbidden();
    }

    public function test_validates_required_fields(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson($this->endpoint, [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'bursam_default_preferred_commodity_type',
            ]);
    }

    public function test_validates_numeric_fields(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson($this->endpoint, [
                'bursam_default_preferred_commodity_type' => 'Not Number Value',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'bursam_default_preferred_commodity_type',
            ]);
    }
}
