<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Settings\Project;

use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Models\User;
use App\Transformers\ProjectSettingsTransformer;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Modules\Grantify\Facades\Grantify;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithAdmin;
use Tests\Traits\InteractsWithSettings;

class ProjectSettingsIndexTest extends TestCase
{
    use RefreshDatabase, InteractsWithAdmin, InteractsWithSettings;

    const BaseUrl = 'api/v1/admin/settings/project';

    private static User $admin;

    private static User $manager;

    private static $projectSettings;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$admin = $this->createAdmin();
        self::$manager = $this->createManager(permissions: perm(Area::SuperAdmin, [Subject::ProjectSettings, Action::Index]));
        self::$projectSettings = $this->app->make(GetProjectSettings::class)->handle();
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_index_lender_settings(): void
    {
        $this->getJson(self::BaseUrl)
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
    public function test_that_auth_user_has_admin_role_can_index_project_settings(): void
    {
        $this->actingAs(self::$admin)
            ->getJson(self::BaseUrl)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$projectSettings, new ProjectSettingsTransformer())
                    ->respond()
                    ->getData(true)
            );
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function test_that_auth_user_has_manager_role_can_index_project_settings(): void
    {
        $this->actingAs(self::$manager)
            ->getJson(self::BaseUrl)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$projectSettings, new ProjectSettingsTransformer())
                    ->respond()
                    ->getData(true)
            );
    }

    /**
     * @return void
     */
    public function test_that_auth_user_without_right_permissions_cannot_index_project_settings(): void
    {
        Grantify::syncPermissionToModel(self::$manager, []);

        $this->actingAs(self::$manager)
            ->getJson(self::BaseUrl)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(
                fn (AssertableJson $json) => $json->where('message', 'User does not have the right permissions.')
                    ->etc()
            );
    }
}
