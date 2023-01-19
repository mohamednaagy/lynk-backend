<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\User;
use App\Transformers\UserTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Grantify\Facades\Grantify;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithUser;

class AdminControllerShowTest extends TestCase
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
        $this->assignPermissionToUser(self::$managerAdminUser, perm(Area::SuperAdmin, [Subject::Admins, Action::Show]));
    }

    public function test_un_auth_cant_show_admin()
    {
        $newSuperAdminUser = $this->createSuperAdminUser();

        $this->getJson('api/v1/admin/admins/'.$newSuperAdminUser->id)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_admin_controller_show_with_super_admin_success()
    {
        $newSuperAdminUser = $this->createSuperAdminUser();

        $this->actingAs(self::$superAdminUser)
            ->getJson('api/v1/admin/admins/'.$newSuperAdminUser->id)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal($newSuperAdminUser, new UserTransformer(Area::SuperAdmin))
                    ->parseIncludes([
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'role',
                        'permissions',
                        'phone_number',
                        'phone_country_code',
                        'formatted_phone_number',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_admin_controller_show_with_manager_success()
    {
        $newManagerUser = $this->createSuperAdminUser(Role::Manager);

        $this->actingAs(self::$managerAdminUser)
            ->getJson('api/v1/admin/admins/'.$newManagerUser->id)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal($newManagerUser, new UserTransformer(Area::SuperAdmin))
                    ->parseIncludes([
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'role',
                        'permissions',
                        'phone_number',
                        'phone_country_code',
                        'formatted_phone_number',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_admin_controller_show_with_manager_no_permissions_unsuccessful()
    {
        $newManagerUser = $this->createSuperAdminUser(Role::Manager);

        Grantify::syncPermissionToModel(self::$managerAdminUser, []);

        $this->actingAs(self::$managerAdminUser)
            ->getJson('api/v1/admin/admins/'.$newManagerUser->id)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', __('User does not have the right permissions.'));
    }
}
