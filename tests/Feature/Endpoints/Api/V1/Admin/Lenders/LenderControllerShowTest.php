<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
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

        [self::$lender, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userAdmin = $this->createSuperAdminUser();
        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(
            self::$userManager,
            perm(Area::SuperAdmin, [Subject::Lenders, Action::Show])
        );
        self::$endpoint = 'api/v1/admin/lenders/'.self::$lender->id;
    }

    /**
     * @return void
     */
    public function test_un_auth_user_cant_show_lender(): void
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
    public function test_admin_user_can_show_lender_successfully(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson(self::$endpoint)
            ->assertOk()
            ->assertExactJson(
                fractal(self::$lender, new CompanyTransformer())
                    ->parseIncludes([
                        'id',
                        'name',
                        'status',
                        'created_at',
                        'unique_name',
                        'company_cr',
                        'does_order_require_approval',
                        'order_cost',
                        'notifications_email',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    /**
     * @return void
     */
    public function test_manager_with_permissions_can_show_lender_successfully(): void
    {
        $this->actingAs(self::$userManager)
            ->getJson(self::$endpoint)
            ->assertOk()
            ->assertExactJson(
                fractal(self::$lender, new CompanyTransformer())
                    ->parseIncludes([
                        'id',
                        'name',
                        'status',
                        'created_at',
                        'unique_name',
                        'company_cr',
                        'does_order_require_approval',
                        'order_cost',
                        'notifications_email',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    /**
     * @return void
     */
    public function test_manager_without_permissions_cant_show_lender(): void
    {
        Grantify::syncPermissionToModel(self::$userManager, []);

        $this->actingAs(self::$userManager)
            ->getJson(self::$endpoint)
            ->assertForbidden();
    }
}
