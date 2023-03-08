<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Traders;

use App\Enums\Area;
use App\Enums\WalletType;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class TraderCompanyControllerUpdateTest extends TestCase
{
    use RefreshDatabase;
    use AssertsAccessByRoleAndArea;

    private static User $superAdmin;

    private static Company $company;

    private static Wallet $wallet;

    private static array $companyDetails;

    private static string $endpoint;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$companyDetails = [
            'name' => 'testCompany',
            'unique_name' => 'companyUniqueName',
            'driver' => 'dmcc',
            'notifications_email' => 'trader@gmail.com',
        ];

        [self::$company, self::$wallet] = $this->createTraderCompany(2000, [
            'unique_name' => 'companyUniqueName',
        ]);

        self::$superAdmin = $this->createSuperAdminUser();
        self::$endpoint = 'api/v1/admin/traders/'.self::$company->id;
    }

    /**
     * @return void
     */
    public function test_unauth_user_cant_access_trader_company_controller_update(): void
    {
        $this->putJson(self::$endpoint, self::$companyDetails)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_other_user_has_not_role_in_super_admin_area_cant_access_trader_company_controller_update()
    {
        $this->assertStatusCodeForAllRolesExceptForArea(403, [Area::SuperAdmin], function ($user, $role) {
            return $this->actingAs($user)
                ->putJson(self::$endpoint, self::$companyDetails);
        });
    }

    public function test_admin_cant_update_company_without_name()
    {
        $this->actingAs(self::$superAdmin)
            ->putJson(self::$endpoint, [
                'unique_name' => 'companyUniqueName',
                'driver' => 'dmcc',
            ])
            ->assertJsonValidationErrorFor('name');
    }

    public function test_admin_cant_update_company_without_unique_name()
    {
        $this->actingAs(self::$superAdmin)
            ->putJson(self::$endpoint, [
                'name' => 'name',
                'driver' => 'dmcc',
            ])
            ->assertJsonValidationErrorFor('unique_name');
    }

    public function test_admin_cant_update_company_without_notifications_email()
    {
        $this->actingAs(self::$superAdmin)
            ->putJson('api/v1/admin/traders/'.self::$company->id, [
                'name' => 'name',
                'unique_name' => 'test_name',
                'driver' => 'dmcc',
            ])
            ->assertJsonValidationErrorFor('notifications_email');
    }

    public function test_admin_cant_update_company_controller_update_without_driver_fake_or_dmcc()
    {
        $this->actingAs(self::$superAdmin)
            ->putJson(self::$endpoint, [
                'name' => 'testCompany',
                'unique_name' => 'companyUniqueName',
                'driver' => 'random',
            ])
            ->assertJsonValidationErrorFor('driver');
    }

    public function test_admin_can_update_company_controller_update_successful()
    {
        $this->actingAs(self::$superAdmin)
            ->putJson(self::$endpoint, self::$companyDetails)
            ->assertStatus(Response::HTTP_OK);
    }

    /**
     * @return void
     */
    public function test_trader_company_controller_update_successful_with_even_same_unique_name(): void
    {
        $this->actingAs(self::$superAdmin)
            ->putJson(self::$endpoint, array_merge(
                self::$companyDetails,
                [
                    'unique_name' => self::$company->unique_name,
                ]
            )
            )
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);
    }

    public function test_admin_can_update_company_controller_update_successful_will_not_affect_existing_wallets()
    {
        $this->actingAs(self::$superAdmin)
            ->putJson(self::$endpoint, self::$companyDetails);

        $hasWallet = self::$company->getWallets(WalletType::CompanyWallet)->count() > 0;
        $this->assertTrue($hasWallet);
    }
}
