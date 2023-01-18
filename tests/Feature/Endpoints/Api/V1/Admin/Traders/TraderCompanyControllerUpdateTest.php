<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Traders;

use App\Enums\Area;
use App\Enums\CompanyType;
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

    private static User $trader;

    private static Company $company;

    private static Wallet $wallet;

    private static array $companyDetails;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$companyDetails = [
            'name' => 'testCompany',
            'unique_name' => 'companyUniqueName',
            'company_cr' => '1234567891',
            'driver' => 'dmcc',
        ];

        [self::$company, self::$wallet] = $this->createCompany(2000, [
            'type' => CompanyType::Trader,
        ]);

        self::$trader = $this->createTraderUser(self::$company->id);
    }

    /**
     * @return void
     */
    public function test_trader_company_controller_update_un_auth_user_cant_store_company(): void
    {
        $this->putJson('api/v1/admin/traders/'.self::$company->id, self::$companyDetails)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_trader_company_controller_update_other_roles_can_not_access()
    {
        $this->assertStatusCodeForAllRolesExceptForArea(403, [Area::Trader], function ($user, $role) {
            return $this->actingAs($user)
                ->putJson('api/v1/admin/traders/'.self::$company->id, self::$companyDetails);
        });
    }

    public function test_trader_company_controller_update_without_name_unsuccessful()
    {
        $this->actingAs(self::$trader)
            ->putJson('api/v1/admin/traders/'.self::$company->id, [
                'unique_name' => 'companyUniqueName',
                'company_cr' => '1234567891',
                'driver' => 'dmcc',
            ])
            ->assertJsonValidationErrorFor('name');
    }

    public function test_trader_company_controller_update_without_unique_name_unsuccessful()
    {
        $this->actingAs(self::$trader)
            ->putJson('api/v1/admin/traders/'.self::$company->id, [
                'name' => 'name',
                'company_cr' => '1234567891',
                'driver' => 'dmcc',
            ])
            ->assertJsonValidationErrorFor('unique_name');
    }

    public function test_trader_company_controller_update_without_company_cr_unsuccessful()
    {
        $this->actingAs(self::$trader)
            ->putJson('api/v1/admin/traders/'.self::$company->id, [
                'name' => 'testCompany',
                'unique_name' => 'companyUniqueName',
                'driver' => 'dmcc',
            ])
            ->assertJsonValidationErrorFor('company_cr');
    }

    public function test_trader_company_controller_update_driver_should_be_in_fake_dmcc_unsuccessful()
    {
        $this->actingAs(self::$trader)
            ->putJson('api/v1/admin/traders/'.self::$company->id, [
                'name' => 'testCompany',
                'unique_name' => 'companyUniqueName',
                'company_cr' => '1234567891',
                'driver' => 'random',
            ])
            ->assertJsonValidationErrorFor('driver');
    }

    public function test_trader_company_controller_update_successful()
    {
        $this->actingAs(self::$trader)
            ->putJson('api/v1/admin/traders/'.self::$company->id, self::$companyDetails)
            ->assertStatus(Response::HTTP_OK);
    }

    public function test_trader_company_controller_update_wallet_checked_successful()
    {
        $this->actingAs(self::$trader)
            ->putJson('api/v1/admin/traders/'.self::$company->id, self::$companyDetails)
            ->assertStatus(Response::HTTP_OK);

        $hasWallet = self::$company->getWallets(WalletType::CompanyWallet)->count() > 0;
        $this->assertTrue($hasWallet);
    }
}
