<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\TraderProducts;

use App\Enums\Role;
use App\Enums\Trader;
use App\Enums\TraderProductStatus;
use App\Models\TraderProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class TraderProductsLiteListTest extends TestCase
{
    use AssertsAccessByRoleAndArea, RefreshDatabase;

    private static User $userAdmin;

    private static User $userManager;

    private static $products;

    private string $endpoint = 'api/v1/admin/trader-products/dropdown-list';

    public function setUp(): void
    {
        parent::setUp();

        self::$userAdmin = $this->createSuperAdminUser();
        self::$userManager = $this->createSuperAdminUser(Role::Manager);

        // Create test products
        self::$products = collect([
            TraderProduct::factory()->create([
                'name' => [
                    'en' => 'Test Product EN',
                    'ar' => 'Test Product AR',
                ],
                'status' => TraderProductStatus::Enabled,
                'provider' => Trader::Bursam,
                'order' => 1,
            ]),
            TraderProduct::factory()->create([
                'name' => [
                    'en' => 'Disabled Product EN',
                    'ar' => 'Disabled Product AR',
                ],
                'status' => TraderProductStatus::DISABLED,
                'provider' => Trader::Bursam,
                'order' => 2,
            ]),
            TraderProduct::factory()->create([
                'name' => [
                    'en' => 'Lynk Product EN',
                    'ar' => 'Lynk Product AR',
                ],
                'status' => TraderProductStatus::Enabled,
                'provider' => Trader::Lynk,
                'order' => 3,
            ]),
        ]);
    }

    public function test_un_auth_user_cant_access_trader_products(): void
    {
        $this->getJson($this->endpoint)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_admin_can_access_trader_products_successfully(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson($this->endpoint)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'code',
                    ],
                ],
            ]);
    }

    public function test_manager_without_permissions_cant_access_trader_products(): void
    {
        $this->actingAs(self::$userManager)
            ->getJson($this->endpoint)
            ->assertForbidden();
    }

    public function test_returns_only_enabled_bursam_products_by_default(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson($this->endpoint)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', self::$products->first()->id)
            ->assertJsonPath('data.0.name', 'Test Product EN');
    }

    public function test_can_filter_by_status(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson("{$this->endpoint}?status=DISABLED")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', self::$products[1]->id);
    }

    public function test_can_filter_by_provider(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson("{$this->endpoint}?provider=lynk")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', self::$products[2]->id);
    }

    public function test_returns_arabic_translation_when_locale_is_ar(): void
    {
        app()->setLocale('ar');

        $this->actingAs(self::$userAdmin)
            ->getJson($this->endpoint)
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Test Product AR');
    }
}
