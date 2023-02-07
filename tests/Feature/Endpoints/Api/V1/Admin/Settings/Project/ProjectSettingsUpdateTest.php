<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Settings\Project;

use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\User;
use App\Settings\Classes\ProjectSettings;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Spatie\LaravelSettings\Settings;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithSettings;
use Tests\Traits\InteractsWithUser;

class ProjectSettingsUpdateTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithSettings, InteractsWithCompany;

    const BaseUrl = 'api/v1/admin/settings/project';

    private static User $admin;

    private static User $manager;

    private static User $managerHasPermission;

    private static Company $company;

    private static User $userLenderAdmin;

    private static $projectSettings;

    private static Settings $projectSettingsClass;

    private static array $projectSettingsData = [];

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$admin = $this->createSuperAdminUser();
        self::$manager = $this->createSuperAdminUser(Role::Manager);
        self::$managerHasPermission = $this->createSuperAdminUser(Role::Manager);

        $this->assignPermissionToUser(self::$managerHasPermission, perm(Area::SuperAdmin, [Subject::ProjectSettings, Action::Edit]));

        [self::$company] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$projectSettings = $this->app->make(GetProjectSettings::class)->handle();
        self::$projectSettingsClass = $this->app->make(ProjectSettings::class);
        self::$projectSettingsData = [
            'company_name' => [
                'ar' => 'لينك',
                'en' => 'Lynk',
            ],
            'company_cr' => '70065554874',
            'vat_id' => '2665656565',
            'vat_rate' => 15,
            'address_line_one' => [
                'ar' => 'شارع الامير فيصل',
                'en' => 'Prince Faisal Street',
            ],
            'address_line_two' => [
                'ar' => 'الرياض، السعودية',
                'en' => 'Riyadh, Saudi Arabia',
            ],
        ];
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_update_lender_settings_failed(): void
    {
        $this->putJson(self::BaseUrl)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_that_un_authorized_user_without_right_role_cant_update_lender_settings_failed(): void
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
    public function test_update_project_settings_on_empty_company_name_failed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, Arr::except(self::$projectSettingsData, ['company_name']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'The company name field is required. (and 2 more errors)',
                'errors' => [
                    'company_name' => [
                        'The company name field is required.',
                    ],
                    'company_name.en' => [
                        'The company name.en field is required.',
                    ],
                    'company_name.ar' => [
                        'The company name.ar field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function test_update_project_settings_on_empty_company_name_en_failed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, Arr::except(self::$projectSettingsData, ['company_name.en']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'The company name must contain 2 items. (and 1 more error)',
                'errors' => [
                    'company_name' => [
                        'The company name must contain 2 items.',
                    ],
                    'company_name.en' => [
                        'The company name.en field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function test_update_project_settings_on_empty_company_name_ar_failed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, Arr::except(self::$projectSettingsData, ['company_name.ar']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'The company name must contain 2 items. (and 1 more error)',
                'errors' => [
                    'company_name' => [
                        'The company name must contain 2 items.',
                    ],
                    'company_name.ar' => [
                        'The company name.ar field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function test_update_project_settings_on_empty_address_line_one_failed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, Arr::except(self::$projectSettingsData, ['address_line_one']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'The address line one field is required. (and 2 more errors)',
                'errors' => [
                    'address_line_one' => [
                        'The address line one field is required.',
                    ],
                    'address_line_one.en' => [
                        'The address line one.en field is required.',
                    ],
                    'address_line_one.ar' => [
                        'The address line one.ar field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function test_update_project_settings_on_empty_address_line_one_en_failed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, Arr::except(self::$projectSettingsData, ['address_line_one.en']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'The address line one must contain 2 items. (and 1 more error)',
                'errors' => [
                    'address_line_one' => [
                        'The address line one must contain 2 items.',
                    ],
                    'address_line_one.en' => [
                        'The address line one.en field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function test_update_project_settings_on_empty_address_line_one_ar_failed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, Arr::except(self::$projectSettingsData, ['address_line_one.ar']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'The address line one must contain 2 items. (and 1 more error)',
                'errors' => [
                    'address_line_one' => [
                        'The address line one must contain 2 items.',
                    ],
                    'address_line_one.ar' => [
                        'The address line one.ar field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function test_update_project_settings_on_empty_address_line_two_failed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, Arr::except(self::$projectSettingsData, ['address_line_two']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'The address line two field is required. (and 2 more errors)',
                'errors' => [
                    'address_line_two' => [
                        'The address line two field is required.',
                    ],
                    'address_line_two.en' => [
                        'The address line two.en field is required.',
                    ],
                    'address_line_two.ar' => [
                        'The address line two.ar field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function test_update_project_settings_on_empty_address_line_two_en_failed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, Arr::except(self::$projectSettingsData, ['address_line_two.en']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'The address line two must contain 2 items. (and 1 more error)',
                'errors' => [
                    'address_line_two' => [
                        'The address line two must contain 2 items.',
                    ],
                    'address_line_two.en' => [
                        'The address line two.en field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function test_update_project_settings_on_empty_address_line_two_ar_failed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, Arr::except(self::$projectSettingsData, ['address_line_two.ar']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'The address line two must contain 2 items. (and 1 more error)',
                'errors' => [
                    'address_line_two' => [
                        'The address line two must contain 2 items.',
                    ],
                    'address_line_two.ar' => [
                        'The address line two.ar field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function test_update_project_settings_on_empty_company_cr_failed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, Arr::except(self::$projectSettingsData, ['company_cr']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'The company CR field is required.',
                'errors' => [
                    'company_cr' => [
                        'The company CR field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function test_update_project_settings_on_empty_vat_id_failed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, Arr::except(self::$projectSettingsData, ['vat_id']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'The vat id field is required.',
                'errors' => [
                    'vat_id' => [
                        'The vat id field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function test_update_project_settings_on_empty_vat_rate_failed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, Arr::except(self::$projectSettingsData, ['vat_rate']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'The vat rate field is required.',
                'errors' => [
                    'vat_rate' => [
                        'The vat rate field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function test_update_project_settings_on_invalid_vat_rate_failed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, array_merge(
                self::$projectSettingsData,
                ['vat_rate' => 'invalid']
            ))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'The vat rate must be a number.',
                'errors' => [
                    'vat_rate' => [
                        'The vat rate must be a number.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function test_that_auth_user_has_admin_role_can_update_project_settings_succeed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, self::$projectSettingsData)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data',
            ]);

        $this->assertEquals(
            (self::$projectSettingsData['vat_rate'] / 100),
            self::$projectSettingsClass->vat_rate
        );
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function test_that_auth_user_has_manager_role_and_right_permission_can_update_project_settings_succeed(): void
    {
        $this->actingAs(self::$managerHasPermission)
            ->putJson(self::BaseUrl, self::$projectSettingsData)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_without_right_permissions_cannot_update_project_settings_failed(): void
    {
        $this->actingAs(self::$manager)
            ->putJson(self::BaseUrl, self::$projectSettingsData)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', 'User does not have the right permissions.');
    }
}
