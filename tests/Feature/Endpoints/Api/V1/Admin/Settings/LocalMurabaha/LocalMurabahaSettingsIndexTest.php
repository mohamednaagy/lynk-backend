<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Settings\LocalMurabaha;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\User;
use App\Transformers\LocalMurabahaSettingsTransformer;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithSettings;
use Tests\Traits\InteractsWithUser;

class LocalMurabahaSettingsIndexTest extends TestCase
{
    use RefreshDatabase, InteractsWithSettings, InteractsWithUser, InteractsWithCompany;

    const BaseUrl = 'api/v1/admin/settings/local-commodity';

    private static User $admin;

    private static User $manager;

    private static User $managerHasPermission;

    private static Company $company;

    private static User $userLenderAdmin;

    private static $localMurabahaSettings;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$admin = $this->createSuperAdminUser();
        self::$manager = $this->createSuperAdminUser(Role::Manager);
        self::$managerHasPermission = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(self::$managerHasPermission, perm(Area::SuperAdmin, [Subject::LocalMurabahaAreaSettings, Action::Index]));

        [self::$company] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin);

        self::$localMurabahaSettings = $this->getLocalMurabahaSettingsClass('LocalMurabaha');
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
    public function test_that_un_authorized_user_without_right_role_cant_index_local_murabaha_settings_failed(): void
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
    public function test_that_auth_user_has_admin_role_can_index_local_murabaha_settings_succeed(): void
    {
        $this->actingAs(self::$admin)
            ->getJson(self::BaseUrl)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$localMurabahaSettings, new LocalMurabahaSettingsTransformer())
                    ->parseIncludes([
                        'default_trade_order_roatation_count',
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
    public function test_that_auth_user_has_manager_role_and_right_permission_can_index_local_murabaha_settings_succeed(): void
    {
        $this->actingAs(self::$managerHasPermission)
            ->getJson(self::BaseUrl)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$localMurabahaSettings, new LocalMurabahaSettingsTransformer())
                    ->parseIncludes([
                        'default_trade_order_roatation_count',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    /**
     * @return void
     */
    public function test_that_auth_user_without_right_permissions_cannot_index_local_murabaha_settings_failed(): void
    {
        $this->actingAs(self::$manager)
            ->getJson(self::BaseUrl)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', 'User does not have the right permissions.');
    }
}
