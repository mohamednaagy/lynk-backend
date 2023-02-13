<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Auth;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use App\Transformers\UserTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class GetAuthAdminTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany;

    private static User $userAdmin;

    private static User $userManager;

    private static User $userLender;

    private static User $lenderApiUser;

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

        self::$userAdmin = $this->createSuperAdminUser();
        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(self::$userManager, perm(Area::SuperAdmin, [Subject::Admins, Action::Show]));
        [self::$company, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$lenderApiUser = $this->createLenderUser(self::$company->id, Role::LenderApiUser);
        self::$userLenderOrderCreator = $this->createLenderUser(self::$company->id, Role::LenderOrderCreator);
        self::$userLenderSupervisor = $this->createLenderUser(self::$company->id, Role::LenderSupervisor);
        self::$userLenderBilling = $this->createLenderUser(self::$company->id, Role::LenderBilling);
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_fetch_his_details(): void
    {
        $this->getJson('api/v1/admin/auth')
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
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
    public function test_that_admin_manager_taken_permissions(): void
    {
        $response = fractal(self::$userManager, new UserTransformer(Area::SuperAdmin))
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
            ])->respond();

        $this->actingAs(self::$userManager)
            ->getJson('api/v1/admin/auth')
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                $response->getData(true)
            );

        $responsePermissions = array_shift($response->original->data->permissions);

        $this->assertTrue($responsePermissions->subject == Area::SuperAdmin.'-'.Subject::Admins);
        $this->assertTrue($responsePermissions->action == Action::Show);
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
                'message',
                'User does not have the right roles.'
            );
    }

    /**
     * @return void
     */
    public function test_that_lender_api_user_can_not_fetch_his_details(): void
    {
        $this->actingAs(self::$lenderApiUser)
            ->getJson('api/v1/admin/auth')
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath(
                'message',
                'User does not have the right roles.'
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
                'message',
                'User does not have the right roles.'
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
                'message',
                'User does not have the right roles.'
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
                'message',
                'User does not have the right roles.'
            );
    }
}
