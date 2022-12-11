<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Settings\Project;

use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Models\User;
use App\Settings\Classes\ProjectSettings;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Modules\Grantify\Facades\Grantify;
use Spatie\LaravelSettings\Settings;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithAdmin;
use Tests\Traits\InteractsWithSettings;

class ProjectSettingsUpdateTest extends TestCase
{
    use RefreshDatabase, InteractsWithAdmin, InteractsWithSettings;

    const BaseUrl = 'api/v1/admin/settings/project';

    private static User $admin;

    private static User $manager;

    private static $projectSettings;

    private static Settings $projectSettingsClass;

    private static array $projectSettingsData = [];

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$admin = $this->createAdmin();
        self::$manager = $this->createManager(permissions: perm(Area::SuperAdmin, [Subject::ProjectSettings, Action::Edit]));
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
    public function test_that_un_auth_user_cant_index_lender_settings(): void
    {
        $this->putJson(self::BaseUrl)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function test_update_project_settings_on_empty_company_name(): void
    {
        unset(self::$projectSettingsData['company_name']);

        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, self::$projectSettingsData)
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
    public function test_update_project_settings_on_empty_company_name_en(): void
    {
        unset(self::$projectSettingsData['company_name']['en']);

        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, self::$projectSettingsData)
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
    public function test_update_project_settings_on_empty_company_name_ar(): void
    {
        unset(self::$projectSettingsData['company_name']['ar']);

        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, self::$projectSettingsData)
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
    public function test_update_project_settings_on_empty_address_line_one(): void
    {
        unset(self::$projectSettingsData['address_line_one']);

        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, self::$projectSettingsData)
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
    public function test_update_project_settings_on_empty_address_line_one_en(): void
    {
        unset(self::$projectSettingsData['address_line_one']['en']);

        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, self::$projectSettingsData)
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
    public function test_update_project_settings_on_empty_address_line_one_ar(): void
    {
        unset(self::$projectSettingsData['address_line_one']['ar']);

        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, self::$projectSettingsData)
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
    public function test_update_project_settings_on_empty_address_line_two(): void
    {
        unset(self::$projectSettingsData['address_line_two']);

        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, self::$projectSettingsData)
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
    public function test_update_project_settings_on_empty_address_line_two_en(): void
    {
        unset(self::$projectSettingsData['address_line_two']['en']);

        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, self::$projectSettingsData)
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
    public function test_update_project_settings_on_empty_address_line_two_ar(): void
    {
        unset(self::$projectSettingsData['address_line_two']['ar']);

        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, self::$projectSettingsData)
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
    public function test_update_project_settings_on_empty_company_cr(): void
    {
        unset(self::$projectSettingsData['company_cr']);

        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, self::$projectSettingsData)
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
    public function test_update_project_settings_on_empty_vat_id(): void
    {
        unset(self::$projectSettingsData['vat_id']);

        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, self::$projectSettingsData)
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
    public function test_update_project_settings_on_empty_vat_rate(): void
    {
        unset(self::$projectSettingsData['vat_rate']);

        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, self::$projectSettingsData)
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
    public function test_update_project_settings_on_invalid_vat_rate(): void
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
    public function test_that_auth_user_has_admin_role_can_update_project_settings(): void
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
    public function test_that_auth_user_has_manager_role_can_update_project_settings(): void
    {
        $this->actingAs(self::$manager)
            ->putJson(self::BaseUrl, self::$projectSettingsData)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data',
            ]
            );
    }

    /**
     * @return void
     */
    public function test_that_auth_user_without_right_permissions_cannot_update_project_settings(): void
    {
        Grantify::syncPermissionToModel(self::$manager, []);

        $this->actingAs(self::$manager)
            ->putJson(self::BaseUrl, self::$projectSettingsData)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(
                fn (AssertableJson $json) => $json->where('message', 'User does not have the right permissions.')
                    ->etc()
            );
    }
}
