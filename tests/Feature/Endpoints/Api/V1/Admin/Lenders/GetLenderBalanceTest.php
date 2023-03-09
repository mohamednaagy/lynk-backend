<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use App\Settings\Classes\ProjectSettings;
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

    private static string $endpoint;

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
        $this->assignPermissionToUser(
            self::$userManager,
            perm(Area::SuperAdmin, [Subject::LenderWallet, Action::Show])
        );
        $projectSettings = app(ProjectSettings::class);
        $projectSettings->vat_rate = 0.15;
        $projectSettings->save();
        self::$endpoint = 'api/v1/admin/lenders/'.self::$lender->id.'/balance';
    }

    /**
     * @return void
     */
    public function test_un_auth_user_cant_get_lender_balance(): void
    {
        $this->getJson(self::$endpoint)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_admin_can_get_lender_balance_successfully(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson(self::$endpoint)
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'available_orders' => '8',
                    'balance' => '20.00',
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_manager__with_permissions_can_get_lender_balance_successfully(): void
    {
        $this->actingAs(self::$userManager)
            ->getJson(self::$endpoint)
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'available_orders' => '8',
                    'balance' => '20.00',
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_manager_without_permissions_cant_get_lender_balance(): void
    {
        Grantify::syncPermissionToModel(self::$userManager, []);

        $this->actingAs(self::$userManager)
            ->getJson(self::$endpoint)
            ->assertForbidden();
    }
}
