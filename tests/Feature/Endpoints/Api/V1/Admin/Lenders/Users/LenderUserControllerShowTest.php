<?php

namespace Endpoints\Api\V1\Admin\Lenders\Users;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use App\Transformers\UserTransformer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class LenderUserControllerShowTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany;

    private static Company $lender;

    private static Wallet $wallet;

    private static User $userAdmin;

    private static User $userManager;

    private static User $userLenderAdmin;

    private static User $userLenderApi;

    private static string $endpoint;

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$lender, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userAdmin = $this->createSuperAdminUser();
        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(self::$userManager, perm(Area::SuperAdmin, [Subject::LenderUsers, Action::Show]));

        self::$userLenderAdmin = $this->createLenderUser(self::$lender->id, Role::LenderAdmin);
        self::$userLenderApi = $this->createLenderUser(self::$lender->id, Role::LenderApiUser);
        self::$endpoint = 'api/v1/admin/lenders/'.self::$lender->id.'/users/';
    }

    public function test_that_un_auth_user_cant_show_lender_user(): void
    {
        $this->getJson(self::$endpoint.self::$userLenderAdmin->id)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_that_auth_admin_user_can_show_lender_user(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson(self::$endpoint.(int) self::$userLenderAdmin->id)
            ->assertOk()
            ->assertExactJson(
                fractal(self::$userLenderAdmin, new UserTransformer(Area::Lender))
                    ->parseIncludes([
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'role',
                        'phone_number',
                        'phone_country_code',
                        'formatted_phone_number',
                    ])->respond()
                    ->getData(true)
            );
    }

    public function test_that_auth_manager_user_can_show_lender_user(): void
    {
        $this->actingAs(self::$userManager)
            ->getJson(self::$endpoint.(int) self::$userLenderAdmin->id)
            ->assertOk()
            ->assertExactJson(
                fractal(self::$userLenderAdmin, new UserTransformer(Area::Lender))
                    ->parseIncludes([
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'role',
                        'phone_number',
                        'phone_country_code',
                        'formatted_phone_number',
                    ])->respond()
                    ->getData(true)
            );
    }

    public function test_that_auth_manager_user_without_permissions_cant_show_lender_user(): void
    {
        Grantify::syncPermissionToModel(self::$userManager, []);

        $this->actingAs(self::$userManager)
            ->getJson(self::$endpoint.(int) self::$userLenderAdmin->id)
            ->assertForbidden();
    }

    public function test_that_auth_admin_user_cant_show_lender_api_user(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson(self::$endpoint.(int) self::$userLenderApi->id)
            ->assertForbidden();
    }
}
