<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class GetLenderBalanceTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    private static Company $lender;

    private static Wallet $wallet;

    private static User $userAdmin;

    private static User $userManager;

    /**
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$lender, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910', 'order_cost' => '200']);
        self::$userAdmin = $this->createSuperAdminUser();
        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(self::$userManager, perm(Area::SuperAdmin, [Subject::LenderWallet, Action::Show]));
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_get_lender_balance(): void
    {
        $this->getJson('api/v1/admin/lenders/'.self::$lender->id.'/balance')
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_can_get_lender_balance(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson('api/v1/admin/lenders/'.self::$lender->id.'/balance')
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'available_orders' => '10',
                    'balance' => '20.00',
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_manager_can_get_lender_balance(): void
    {
        $this->actingAs(self::$userManager)
            ->getJson('api/v1/admin/lenders/'.self::$lender->id.'/balance')
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'available_orders' => '10',
                    'balance' => '20.00',
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_manager_without_permissions_cant_get_lender_balance(): void
    {
        Grantify::syncPermissionToModel(self::$userManager, []);

        $this->actingAs(self::$userManager)
            ->getJson('api/v1/admin/lenders/'.self::$lender->id.'/balance')
            ->assertForbidden();
    }
}
