<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Settings\WakalaTemplates;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithAdmin;
use Tests\Traits\InteractsWithLender;

class WakalaTemplateSettingsUpdateTest extends TestCase
{
    use RefreshDatabase, InteractsWithAdmin, InteractsWithLender;

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

    private static array $wakalaSettingsData = [];

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
            perm(Area::SuperAdmin, [Subject::WakalaTemplates, Action::Edit])
        );
        [self::$company] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'lenderAdmin@bim.com');
        self::$clientWakalaUrl = self::BaseUrl.self::$wakalaTemplatesTypes['client'];
        self::$companyWakalaUrl = self::BaseUrl.self::$wakalaTemplatesTypes['company'];
        self::$wakalaSettingsData = [
            'wakala_template' => '<p>This is the wakala template test</p>',
        ];
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_update_wakala_template_settings_failed(): void
    {
        $this->putJson(self::$clientWakalaUrl)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_that_un_authorized_user_without_right_role_cant_update_wakala_template_settings_failed(): void
    {
        $this->actingAs(self::$userLenderAdmin)
            ->getJson(self::$clientWakalaUrl)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', 'User does not have the right roles.');
    }

    /**
     * @return void
     */
    public function test_update_client_wakala_template_settings_on_empty_wakala_template_failed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::$clientWakalaUrl, Arr::except(self::$wakalaSettingsData, ['wakala_template']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'The wakala template field is required.',
                'errors' => [
                    'wakala_template' => [
                        'The wakala template field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_update_company_wakala_template_settings_on_empty_wakala_template_failed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::$companyWakalaUrl, Arr::except(self::$wakalaSettingsData, ['wakala_template']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'The wakala template field is required.',
                'errors' => [
                    'wakala_template' => [
                        'The wakala template field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_cannot_update_wakala_template_settings_on_invalid_wakala_template_type_parameter_failed(): void
    {
        $this->actingAs(self::$admin)
            ->getJson(self::BaseUrl.self::$wakalaTemplatesTypes['invalid'])
            ->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJsonPath('message', '');
    }

    /**
     * @return void
     */
    public function test_that_auth_user_has_admin_role_can_update_client_wakala_template_settings_succeed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::$clientWakalaUrl, self::$wakalaSettingsData)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_has_admin_role_can_update_company_wakala_template_settings_succeed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::$companyWakalaUrl, self::$wakalaSettingsData)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_has_manager_role_and_right_permission_can_update_client_wakala_template_settings_succeed(): void
    {
        $this->actingAs(self::$managerHasPermission)
            ->putJson(self::$clientWakalaUrl, self::$wakalaSettingsData)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_has_manager_role_and_right_permission_can_update_company_wakala_template_settings_succeed(): void
    {
        $this->actingAs(self::$managerHasPermission)
            ->putJson(self::$companyWakalaUrl, self::$wakalaSettingsData)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_without_right_permissions_cannot_update_client_wakala_template_settings_failed(): void
    {
        $this->actingAs(self::$manager)
            ->putJson(self::$clientWakalaUrl, self::$wakalaSettingsData)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', 'User does not have the right permissions.');
    }

    /**
     * @return void
     */
    public function test_that_auth_user_without_right_permissions_cannot_update_company_wakala_template_settings_failed(): void
    {
        $this->actingAs(self::$manager)
            ->putJson(self::$companyWakalaUrl, self::$wakalaSettingsData)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', 'User does not have the right permissions.');
    }
}
