<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Settings\Project;

use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\User;
use App\Transformers\ProjectSettingsTransformer;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;
use Tests\Traits\InteractsWithSettings;

class ProjectSettingsIndexTest extends TestCase
{
    use RefreshDatabase, InteractsWithSettings, AssertsAccessByRoleAndArea;

    const BaseUrl = 'api/v1/admin/settings/project';

    private static User $admin;

    private static User $manager;

    private static User $managerHasPermission;

    private static Company $company;

    private static User $userLenderAdmin;

    private static $projectSettings;

    /**
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$admin = $this->createSuperAdminUser();
        self::$manager = $this->createSuperAdminUser(Role::Manager);
        self::$managerHasPermission = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(self::$managerHasPermission, perm(Area::SuperAdmin, [Subject::ProjectSettings, Action::Index]));
        [self::$company] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$projectSettings = $this->app->make(GetProjectSettings::class)->handle();
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
    public function test_that_auth_user_has_admin_role_can_index_project_settings_succeed(): void
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
    public function test_that_auth_user_has_manager_role_can_index_project_settings_succeed(): void
    {
        $this->actingAs(self::$managerHasPermission)
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
    public function test_that_auth_user_without_right_permissions_cannot_index_project_settings_failed(): void
    {
        $this->assertStatusCodeExceptForPermissions(Response::HTTP_FORBIDDEN, [
            Area::SuperAdmin => [
                [Subject::All, Action::Manage],
                [Subject::ProjectSettings, Action::Index],
                [Subject::ProjectSettings, Action::Manage],
                [Subject::Lenders, Action::Create],
                [Subject::Lenders, Action::Edit],
                [Subject::Lenders, Action::Manage],
            ],
        ], function ($user, $role, $permission) {
            return $this->actingAs($user)
                ->getJson(self::BaseUrl);
        });
    }
}
