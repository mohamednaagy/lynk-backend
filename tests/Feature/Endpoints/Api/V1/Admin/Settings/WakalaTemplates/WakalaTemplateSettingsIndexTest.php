<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Settings\WakalaTemplates;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithSettings;
use Tests\Traits\InteractsWithUser;

class WakalaTemplateSettingsIndexTest extends TestCase
{
    use RefreshDatabase, InteractsWithCompany, InteractsWithSettings, InteractsWithUser;

    const BaseUrl = 'api/v1/admin/wakala-templates/';

    private static string $clientWakalaUrl;

    private static string $companyWakalaUrl;

    private static array $wakalaTemplatesTypes = [
        'client' => 'client',
        'company' => 'company',
        'invalid' => 'invalidType',
    ];

    private static User $admin;

    private static User $manager;

    private static User $managerHasPermission;

    private static Company $company;

    private static User $userLenderAdmin;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$admin = $this->createSuperAdminUser();
        self::$manager = $this->createSuperAdminUser(Role::Manager);
        self::$managerHasPermission = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(self::$managerHasPermission, perm(Area::SuperAdmin, [Subject::WakalaTemplates, Action::Index]));
        [self::$company] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$clientWakalaUrl = self::BaseUrl.self::$wakalaTemplatesTypes['client'];
        self::$companyWakalaUrl = self::BaseUrl.self::$wakalaTemplatesTypes['company'];
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_index_wakala_template_settings_failed(): void
    {
        $this->getJson(self::$clientWakalaUrl)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_that_un_authorized_user_without_right_role_cant_index_wakala_template_settings_failed(): void
    {
        $this->actingAs(self::$userLenderAdmin)
            ->getJson(self::$clientWakalaUrl)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', 'User does not have the right roles.');
    }

    /**
     * @return void
     */
    public function test_that_auth_user_has_admin_role_can_index_client_wakala_template_settings_succeed(): void
    {
        $this->actingAs(self::$admin)
            ->getJson(self::$clientWakalaUrl)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'wakala_template',
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_has_admin_role_can_index_company_wakala_template_settings_succeed(): void
    {
        $this->actingAs(self::$admin)
            ->getJson(self::$companyWakalaUrl)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'wakala_template',
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_has_manager_role_and_right_permission_can_index_client_wakala_template_settings_succeed(): void
    {
        $this->actingAs(self::$managerHasPermission)
            ->getJson(self::$clientWakalaUrl)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'wakala_template',
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_has_manager_role_and_right_permission_can_index_company_wakala_template_settings_succeed(): void
    {
        $this->actingAs(self::$managerHasPermission)
            ->getJson(self::$companyWakalaUrl)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'wakala_template',
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_without_right_permissions_cannot_index_wakala_template_settings_failed(): void
    {
        $this->actingAs(self::$manager)
            ->getJson(self::$clientWakalaUrl)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', 'User does not have the right permissions.');
    }

    /**
     * @return void
     */
    public function test_that_auth_user_cannot_index_wakala_template_settings_on_invalid_wakala_template_type_parameter_failed(): void
    {
        $this->actingAs(self::$manager)
            ->getJson(self::BaseUrl.self::$wakalaTemplatesTypes['invalid'])
            ->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJsonPath('message', 'The route api/v1/admin/wakala-templates/invalidType could not be found.');
    }
}
