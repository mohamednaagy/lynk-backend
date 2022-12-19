<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Settings\Lender;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\User;
use App\Transformers\LenderSettingsTransformer;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithAdmin;
use Tests\Traits\InteractsWithLender;
use Tests\Traits\InteractsWithSettings;

class LenderSettingsIndexTest extends TestCase
{
    use RefreshDatabase, InteractsWithAdmin, InteractsWithSettings, InteractsWithLender;

    const BaseUrl = 'api/v1/admin/settings/lender';

    private static User $admin;

    private static User $manager;

    private static User $managerHasPermission;

    private static Company $company;

    private static User $userLenderAdmin;

    private static $lenderSettings;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$admin = $this->createAdmin();
        self::$manager = $this->createManager();
        self::$managerHasPermission = $this->createManager(
            'managerHasPermission@bim.com',
            perm(Area::SuperAdmin, [Subject::LenderAreaSettings, Action::Index])
        );
        [self::$company] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'lenderAdmin@bim.com');
        self::$lenderSettings = $this->getSettingsClass(Area::Lender);
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_index_lender_settings_failed(): void
    {
        $this->getJson(self::BaseUrl)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_that_un_authorized_user_without_right_role_cant_index_lender_settings_failed(): void
    {
        $this->actingAs(self::$userLenderAdmin)
            ->getJson(self::BaseUrl)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', 'User does not have the right roles.');
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function test_that_auth_user_has_admin_role_can_index_lender_settings_succeed(): void
    {
        $this->actingAs(self::$admin)
            ->getJson(self::BaseUrl)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$lenderSettings, new LenderSettingsTransformer())
                    ->parseIncludes([
                        'default_order_cost',
                        'email_verification_enabled',
                        'default_does_order_require_approval',
                        'default_company_registration_status',
                        'default_company_status_created_by_operation',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function test_that_auth_user_has_manager_role_and_right_permission_can_index_lender_settings_succeed(): void
    {
        $this->actingAs(self::$managerHasPermission)
            ->getJson(self::BaseUrl)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$lenderSettings, new LenderSettingsTransformer())
                    ->parseIncludes([
                        'default_order_cost',
                        'email_verification_enabled',
                        'default_does_order_require_approval',
                        'default_company_registration_status',
                        'default_company_status_created_by_operation',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    /**
     * @return void
     */
    public function test_that_auth_user_without_right_permissions_cannot_index_lender_settings_failed(): void
    {
        $this->actingAs(self::$manager)
            ->getJson(self::BaseUrl)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', 'User does not have the right permissions.');
    }
}
