<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders;

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
use Tests\Traits\InteractsWithAdmin;
use Tests\Traits\InteractsWithLender;

class LenderUserControllerShowTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender, InteractsWithAdmin;

    private static Company $company;

    private static Wallet $wallet;

    private static User $userAdmin;

    private static User $userManager;

    private static User $userLenderAdmin;

    private static User $userLenderApi;

    /**
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userAdmin = $this->createAdmin('admin@bim.com');
        self::$userManager = $this->createManager(
            'manager@bim.com',
            perm(Area::SuperAdmin, [Subject::LenderUsers, Action::Show]),
        );
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'lenderAdmin@bim.com');
        self::$userLenderApi = $this->createLenderUser(self::$company->id, Role::LenderApiUser, 'lenderApi@bim.com');
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_show_company_user(): void
    {
        $this->getJson('api/v1/admin/companies/'.self::$company->id.'/users/'.self::$userLenderAdmin->id)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_admin_user_can_show_company_user(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson('api/v1/admin/companies/'.self::$company->id.'/users/'.(int) self::$userLenderAdmin->id)
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

    /**
     * @return void
     */
    public function test_that_auth_manager_user_can_show_company_user(): void
    {
        $this->actingAs(self::$userManager)
            ->getJson('api/v1/admin/companies/'.self::$company->id.'/users/'.(int) self::$userLenderAdmin->id)
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

    /**
     * @return void
     */
    public function test_that_auth_manager_user_without_permissions_cant_show_company_user(): void
    {
        Grantify::syncPermissionToModel(self::$userManager, []);

        $this->actingAs(self::$userManager)
            ->getJson('api/v1/admin/companies/'.self::$company->id.'/users/'.(int) self::$userLenderAdmin->id)
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_that_auth_admin_user_cant_show_lender_api_user(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson('api/v1/admin/companies/'.self::$company->id.'/users/'.(int) self::$userLenderApi->id)
            ->assertForbidden();
    }
}
