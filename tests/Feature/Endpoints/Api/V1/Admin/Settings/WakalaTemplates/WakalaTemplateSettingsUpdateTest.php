<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Settings\WakalaTemplates;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Modules\Grantify\Facades\Grantify;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithAdmin;

class WakalaTemplateSettingsUpdateTest extends TestCase
{
    use RefreshDatabase, InteractsWithAdmin;

    const BaseUrl = 'api/v1/admin/wakala-templates/';

    private static string $wakalaUrl;

    private static array $wakalaTemplatesTypes = [
        'client' => 'client',
        'company' => 'company',
        'invalid' => 'invalidType',
    ];

    private static User $admin;

    private static User $manager;

    private static array $wakalaSettingsData = [];

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$admin = $this->createAdmin();
        self::$manager = $this->createManager(permissions: perm(Area::SuperAdmin, [Subject::WakalaTemplates, Action::Edit]));
        self::$wakalaUrl = self::BaseUrl.self::$wakalaTemplatesTypes['client'];
        self::$wakalaSettingsData = [
            'wakala_template' => '<p>This is the wakala template test</p>',
        ];
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_index_wakala_template_settings(): void
    {
        $this->putJson(self::$wakalaUrl)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_update_wakala_template_settings_on_empty_wakala_template(): void
    {
        unset(self::$wakalaSettingsData['wakala_template']);

        $this->actingAs(self::$admin)
            ->putJson(self::$wakalaUrl, self::$wakalaSettingsData)
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
    public function test_that_auth_user_cannot_index_wakala_template_settings_on_invalid_wakala_template_type_parameter(): void
    {
        $this->actingAs(self::$manager)
            ->getJson(self::BaseUrl.self::$wakalaTemplatesTypes['invalid'])
            ->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJson(
                fn (AssertableJson $json) => $json->where('message', '')
                    ->etc()
            );
    }

    /**
     * @return void
     */
    public function test_that_auth_user_has_admin_role_can_update_wakala_template_settings(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::$wakalaUrl, self::$wakalaSettingsData)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_has_manager_role_can_update_wakala_template_settings(): void
    {
        $this->actingAs(self::$manager)
            ->putJson(self::$wakalaUrl, self::$wakalaSettingsData)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data',
            ]
            );
    }

    /**
     * @return void
     */
    public function test_that_auth_user_without_right_permissions_cannot_update_wakala_template_settings(): void
    {
        Grantify::syncPermissionToModel(self::$manager, []);

        $this->actingAs(self::$manager)
            ->putJson(self::$wakalaUrl, self::$wakalaSettingsData)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(
                fn (AssertableJson $json) => $json->where('message', 'User does not have the right permissions.')
                    ->etc()
            );
    }
}
