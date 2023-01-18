<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Grantify\Facades\Grantify;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithUser;

class AdminControllerDestroyTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser;

    private static User $superAdminUser;

    private static User $managerAdminUser;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$superAdminUser = $this->createSuperAdminUser();
        self::$managerAdminUser = $this->createSuperAdminUser(Role::Manager);

        $this->assignPermissionToUser(self::$managerAdminUser, perm(Area::SuperAdmin, [Subject::Admins, Action::Delete]));
    }

    public function test_un_auth_cant_index_admins()
    {
        $newSuperAdminUser = $this->createSuperAdminUser();

        $this->deleteJson('api/v1/admin/admins/'.$newSuperAdminUser->id)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_admin_controller_delete_with_super_admin_success()
    {
        $newSuperAdminUser = $this->createSuperAdminUser();

        $this->actingAs(self::$superAdminUser)
            ->deleteJson('api/v1/admin/admins/'.$newSuperAdminUser->id)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['data']);

        $this->actingAs(self::$superAdminUser)
            ->postJson('api/v1/admin/admins', [
                'first_name' => 'admin',
                'last_name' => 'admin',
                'email' => 'newadmin@bim.com',
                'role' => Role::Admin,
                'redirect_url' => 'http://localhost:8000/api/v1/admin/sign-up',
            ])
            ->assertStatus(Response::HTTP_CREATED);
    }

    public function test_admin_controller_delete_with_manager_no_permissions_unsuccessful()
    {
        $newManagerUser = $this->createSuperAdminUser(Role::Manager);
        Grantify::syncPermissionToModel(self::$managerAdminUser, []);

        $this->actingAs(self::$managerAdminUser)
            ->deleteJson('api/v1/admin/admins/'.$newManagerUser->id)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', __('User does not have the right permissions.'));
    }
}
