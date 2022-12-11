<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Auth;

use App\Enums\Area;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use App\Transformers\UserTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Grantify\Facades\Grantify;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithAdmin;
use Tests\Traits\InteractsWithLender;

class GetAuthAdminTest extends TestCase
{
    use RefreshDatabase, InteractsWithAdmin, InteractsWithLender;

    private static User $userAdmin;

    private static User $userManager;

    private static User $userLender;

    private static User $LenderApiUser;

    private static User $userLenderSupervisor;

    private static User $userLenderOrderCreator;

    private static User $userLenderBilling;

    private static Wallet $wallet;

    private static Company $company;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$userAdmin = $this->createAdmin();
        self::$userManager = $this->createAdmin();
        Grantify::syncRoleToModel(self::$userManager, Role::Manager);

        [self::$company, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'lenderAdmin@bim.com');
        self::$LenderApiUser = $this->createLenderUser(self::$company->id, Role::LenderApiUser, 'LenderApiUser@bim.com');
        self::$userLenderOrderCreator = $this->createLenderUser(self::$company->id, Role::LenderOrderCreator, 'LenderOrderCreator@bim.com');
        self::$userLenderSupervisor = $this->createLenderUser(self::$company->id, Role::LenderSupervisor, 'LenderSupervisor@bim.com');
        self::$userLenderBilling = $this->createLenderUser(self::$company->id, Role::LenderBilling, 'LenderBilling@bim.com');
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_fetch_his_details(): void
    {
        $this->getJson('api/v1/admin/auth')
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_can_fetch_his_details(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson('api/v1/admin/auth')
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$userAdmin, new UserTransformer(Area::SuperAdmin))
                    ->parseIncludes([
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'role',
                        'permissions',
                        'phone_number',
                        'phone_country_code',
                        'formatted_phone_number',
                    ])->respond()->getData(true)
            );
    }

    /**
     * @return void
     */
    public function test_that_admin_manager_can_fetch_his_details(): void
    {
        $this->actingAs(self::$userManager)
            ->getJson('api/v1/admin/auth')
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$userManager, new UserTransformer(Area::SuperAdmin))
                    ->parseIncludes([
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'role',
                        'permissions',
                        'phone_number',
                        'phone_country_code',
                        'formatted_phone_number',
                    ])->respond()->getData(true)
            );
    }

    /**
     * @return void
     */
    public function test_that_lender_admin_can_not_fetch_his_details(): void
    {
        $this->actingAs(self::$userLender)
            ->getJson('api/v1/admin/auth')
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath(
                'message', 'User does not have the right roles.'
            );
    }

    /**
     * @return void
     */
    public function test_that_lender_api_user_can_not_fetch_his_details(): void
    {
        $this->actingAs(self::$LenderApiUser)
            ->getJson('api/v1/admin/auth')
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath(
                'message', 'User does not have the right roles.'
            );
    }

    /**
     * @return void
     */
    public function test_that_lender_supervisor_can_not_fetch_his_details(): void
    {
        $this->actingAs(self::$userLenderSupervisor)
            ->getJson('api/v1/admin/auth')
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath(
                'message', 'User does not have the right roles.'
            );
    }

    /**
     * @return void
     */
    public function test_that_lender_order_creator_can_not_fetch_his_details(): void
    {
        $this->actingAs(self::$userLenderOrderCreator)
            ->getJson('api/v1/admin/auth')
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath(
                'message', 'User does not have the right roles.'
            );
    }

    /**
     * @return void
     */
    public function test_that_lender_billing_can_not_fetch_his_details(): void
    {
        $this->actingAs(self::$userLenderBilling)
            ->getJson('api/v1/admin/auth')
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath(
                'message', 'User does not have the right roles.'
            );
    }
}
