<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Settings\Lender;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\User;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithSettings;
use Tests\Traits\InteractsWithUser;

class LocalMurabahaSettingsUpdateTest extends TestCase
{
    use InteractsWithCompany, InteractsWithSettings, InteractsWithUser, RefreshDatabase;

    const BaseUrl = 'api/v1/admin/settings/local-commodity';

    private static User $admin;

    private static User $manager;

    private static User $managerHasPermission;

    private static Company $company;

    private static User $userLenderAdmin;

    private static $localMurabahaSettings;

    private static array $localMurabahaSettingsData = [];

    public function setUp(): void
    {
        parent::setUp();

        self::$admin = $this->createSuperAdminUser();
        self::$manager = $this->createSuperAdminUser(Role::Manager);
        self::$managerHasPermission = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(self::$managerHasPermission, perm(Area::SuperAdmin, [Subject::LocalMurabahaAreaSettings, Action::Edit]));

        [self::$company] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$localMurabahaSettings = $this->getLocalMurabahaSettingsClass('LocalMurabaha');
        self::$localMurabahaSettingsData = [
            'default_trade_order_roatation_count' => 1,
            'default_contract_sign_time_limit'  => 45,
        ];
    }

    public function test_that_un_auth_user_cant_update_local_murabaha_settings_failed(): void
    {
        $this->putJson(self::BaseUrl)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_that_un_authorized_user_without_right_role_cant_update_local_murabaha_settings_failed(): void
    {
        $this->actingAs(self::$userLenderAdmin)
            ->getJson(self::BaseUrl)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', 'User does not have the right roles.');
    }

    /**
     * @throws Exception
     */
    public function test_update_local_murabaha_settings_on_empty_default_trade_order_roatation_count_failed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, Arr::except(self::$localMurabahaSettingsData, ['default_trade_order_roatation_count']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'The default trade order roatation count field is required. (and 1 more error)',
                'errors' => [
                    'default_trade_order_roatation_count' => [
                        'The default trade order roatation count field is required.',
                    ],
                    'default_contract_sign_time_limit' => [
                        'The default contract sign time limit field is required.',
                    ],
                ],
            ]);
    }


    /**
     * @throws Exception
     */
    public function test_update_local_murabaha_settings_on_invalid_default_trade_order_roatation_count_failed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, array_merge(
                self::$localMurabahaSettingsData,
                ['default_trade_order_roatation_count' => 'invalid',
                    'default_contract_sign_time_limit' => 0]
            ))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'The selected default trade order roatation count is invalid. (and 1 more error)',
                'errors' => [
                    'default_trade_order_roatation_count' => [
                        'The selected default trade order roatation count is invalid.',
                    ],
                    'default_contract_sign_time_limit' => [
                        'The default contract sign time limit must be greater than 0.',
                    ],
                ],
            ]);
    }


    /**
     * @throws Exception
     */
    public function test_that_auth_user_has_admin_role_can_update_local_murabaha_settings_succeed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, self::$localMurabahaSettingsData)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data',
            ]);
    }

    /**
     * @throws Exception
     */
    public function test_that_auth_user_has_manager_role_and_right_permission_can_update_local_murabaha_settings_succeed(): void
    {
        $this->actingAs(self::$managerHasPermission)
            ->putJson(self::BaseUrl, self::$localMurabahaSettingsData)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data',
            ]);
    }

    public function test_that_auth_user_without_right_permissions_cannot_update_local_murabaha_settings_failed(): void
    {
        $this->actingAs(self::$manager)
            ->putJson(self::BaseUrl, self::$localMurabahaSettingsData)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', 'User does not have the right permissions.');
    }
}
