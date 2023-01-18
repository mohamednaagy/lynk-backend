<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Traders;

use App\Enums\Area;
use App\Enums\WalletType;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class TraderCompanyControllerStoreTest extends TestCase
{
    use RefreshDatabase;
    use AssertsAccessByRoleAndArea;

    private static User $trader;

    private static company $company;

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

        [self::$company] = $this->createTraderCompany();

        self::$trader = $this->createTraderUser(self::$company->id);
    }

    /**
     * @return void
     */
    public function test_trader_company_controller_store_un_auth_user_cant_store_company(): void
    {
        $this->postJson('api/v1/admin/traders', self::$companyDetails)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_trader_company_controller_store_other_roles_can_not_access()
    {
        $this->assertStatusCodeForAllRolesExceptForArea(403, [Area::Trader], function ($user, $role) {
            return $this->actingAs($user)
                ->postJson('api/v1/admin/traders', self::$companyDetails);
        });
    }

    public function test_trader_company_controller_store_without_name_unsuccessful()
    {
        $this->actingAs(self::$trader)
            ->postJson('api/v1/admin/traders', [
                'unique_name' => 'companyUniqueName',
                'company_cr' => '1234567891',
                'driver' => 'dmcc',
            ])
            ->assertJsonValidationErrorFor('name');
    }

    public function test_trader_company_controller_store_without_unique_name_unsuccessful()
    {
        $this->actingAs(self::$trader)
            ->postJson('api/v1/admin/traders', [
                'name' => 'name',
                'company_cr' => '1234567891',
                'driver' => 'dmcc',
            ])
            ->assertJsonValidationErrorFor('unique_name');
    }

    public function test_trader_company_controller_store_without_company_cr_unsuccessful()
    {
        $this->actingAs(self::$trader)
            ->postJson('api/v1/admin/traders', [
                'name' => 'testCompany',
                'unique_name' => 'companyUniqueName',
                'driver' => 'dmcc',
            ])
            ->assertJsonValidationErrorFor('company_cr');
    }

    public function test_trader_company_controller_driver_should_be_in_dmcc_fake_unsuccessful()
    {
        $this->actingAs(self::$trader)
            ->postJson('api/v1/admin/traders', [
                'name' => 'testCompany',
                'unique_name' => 'companyUniqueName',
                'company_cr' => '1234567891',
                'driver' => 'random',
            ])
            ->assertJsonValidationErrorFor('driver');
    }

    public function test_trader_company_controller_store_successful()
    {
        $this->actingAs(self::$trader)
            ->postJson('api/v1/admin/traders', self::$companyDetails)
            ->assertStatus(Response::HTTP_OK);
    }

    public function test_trader_company_controller_store_wallet_created_successful()
    {
        $this->actingAs(self::$trader)
            ->postJson('api/v1/admin/traders', self::$companyDetails)
            ->assertStatus(Response::HTTP_OK);

        $company = Company::query()
            ->where('unique_name', self::$companyDetails['unique_name'])
            ->first();
        $hasWallet = $company->getWallets(WalletType::CompanyWallet)->count() > 0;
        $this->assertTrue($hasWallet);
    }
}
