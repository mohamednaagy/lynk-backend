<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Enums\TraderOrderMode;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use App\Transformers\CompanyTransformer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class LenderControllerShowTest extends TestCase
{
    use AssertsAccessByRoleAndArea, RefreshDatabase;

    private static Company $lender;

    private static Wallet $wallet;

    private static User $userAdmin;

    private static User $userManager;

    private static string $endpoint;

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$lender = $this->createLenderCompanyWithStandardOrderCost('2000', [
            'trading_mode' => TraderOrderMode::Automatic,
        ]);

        self::$userAdmin = $this->createSuperAdminUser();
        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(
            self::$userManager,
            perm(Area::SuperAdmin, [Subject::Lenders, Action::Show])
        );
        self::$endpoint = 'api/v1/admin/lenders/'.self::$lender->id;
    }

    public function test_un_auth_user_cant_show_lender(): void
    {
        $this->getJson(self::$endpoint)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_admin_user_can_show_lender_successfully(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson(self::$endpoint)
            ->assertOk()
            ->assertJson(
                fractal(self::$lender, new CompanyTransformer())
                    ->parseIncludes([
                        'id',
                        'name',
                        'status',
                        'created_at',
                        'unique_name',
                        'company_cr',
                        'does_order_require_approval',
                        'notify_borrowers_about_order_updates',
                        'force_unique_reference_number',
                        'require_initiate_trade_request',
                        'order_cost_tiers',
                        'notifications_email',
                        'notify_admins_about_new_orders',
                        'trading_mode',
                        'preferred_market_type',
                        'preferred_commodity_types',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_manager_with_permissions_can_show_lender_successfully(): void
    {
        $this->actingAs(self::$userManager)
            ->getJson(self::$endpoint)
            ->assertOk()
            ->assertJson(
                fractal(self::$lender, new CompanyTransformer())
                    ->parseIncludes([
                        'id',
                        'name',
                        'status',
                        'created_at',
                        'unique_name',
                        'company_cr',
                        'does_order_require_approval',
                        'notify_borrowers_about_order_updates',
                        'force_unique_reference_number',
                        'require_initiate_trade_request',
                        'order_cost_tiers',
                        'notifications_email',
                        'notify_admins_about_new_orders',
                        'trading_mode',
                        'preferred_market_type',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_manager_without_permissions_cant_show_lender(): void
    {
        Grantify::syncPermissionToModel(self::$userManager, []);

        $this->actingAs(self::$userManager)
            ->getJson(self::$endpoint)
            ->assertForbidden();
    }
}
