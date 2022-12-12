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
use Tests\Traits\InteractsWithSettings;

class WakalaTemplateSettingsIndexTest extends TestCase
{
    use RefreshDatabase, InteractsWithAdmin, InteractsWithSettings;

    const BaseUrl = 'api/v1/admin/wakala-templates/';

    private static string $wakalaUrl;

    private static array $wakalaTemplatesTypes = [
        'client' => 'client',
        'company' => 'company',
        'invalid' => 'invalidType',
    ];

    private static User $admin;

    private static User $manager;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$admin = $this->createAdmin();
        self::$manager = $this->createManager(permissions: perm(Area::SuperAdmin, [Subject::WakalaTemplates, Action::Index]));
        self::$wakalaUrl = self::BaseUrl.self::$wakalaTemplatesTypes['client'];
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_index_wakala_template_settings(): void
    {
        $this->getJson(self::$wakalaUrl)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_has_admin_role_can_index_wakala_template_settings(): void
    {
        $this->actingAs(self::$admin)
            ->getJson(self::$wakalaUrl)
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
    public function test_that_auth_user_has_manager_role_can_index_wakala_template_settings(): void
    {
        $this->actingAs(self::$manager)
            ->getJson(self::$wakalaUrl)
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
    public function test_that_auth_user_without_right_permissions_cannot_index_wakala_template_settings(): void
    {
        Grantify::syncPermissionToModel(self::$manager, []);

        $this->actingAs(self::$manager)
            ->getJson(self::$wakalaUrl)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(
                fn (AssertableJson $json) => $json->where('message', 'User does not have the right permissions.')
                    ->etc()
            );
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
}
