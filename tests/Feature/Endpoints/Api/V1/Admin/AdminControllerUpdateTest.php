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
use Tests\Traits\InteractsWithAdmin;

class AdminControllerUpdateTest extends TestCase
{
    use RefreshDatabase, InteractsWithAdmin;

    private static User $superAdminUser;

    private static User $managerAdminUser;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$superAdminUser = $this->createAdmin();

        self::$managerAdminUser = $this->createManager(
            'manager@bim.com',
            perm(Area::SuperAdmin, [Subject::Admins, Action::Edit]),
        );
    }

    public function test_un_auth_cant_update_admin()
    {
        $newSuperAdminUser = $this->createAdmin('newadmin@bim.com');

        $this->putJson('api/v1/admin/admins/'.$newSuperAdminUser->id, [
            'first_name' => 'admin',
            'last_name' => 'admin',
            'email' => 'newadmin@bim.com',
            'role' => Role::Admin,
        ])->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_admin_controller_update_with_super_admin_success()
    {
        $newSuperAdminUser = $this->createAdmin('newadmin@bim.com');

        $this->actingAs(self::$superAdminUser)
            ->putJson('api/v1/admin/admins/'.$newSuperAdminUser->id, [
                'first_name' => 'admin',
                'last_name' => 'admin',
                'email' => 'newadmin@bim.com',
                'role' => Role::Admin,
            ])
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal($newSuperAdminUser->fresh(), new UserTransformer(Area::SuperAdmin))
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

    public function test_admin_controller_update_with_manager_success()
    {
        $newManagerUser = $this->createManager('newmanager@bim.com');

        $this->actingAs(self::$managerAdminUser)
            ->putJson('api/v1/admin/admins/'.$newManagerUser->id, [
                'first_name' => 'manager',
                'last_name' => 'manager',
                'email' => 'newadmin@bim.com',
                'role' => Role::Admin,
            ])
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal($newManagerUser->fresh(), new UserTransformer(Area::SuperAdmin))
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

    public function test_admin_controller_update_with_manager_no_permissions_unsuccessful()
    {
        $newManagerUser = $this->createManager('newmanager@bim.com');

        Grantify::syncPermissionToModel(self::$managerAdminUser, []);

        $this->actingAs(self::$managerAdminUser)
            ->putJson('api/v1/admin/admins/'.$newManagerUser->id, [
                'first_name' => 'manager',
                'last_name' => 'manager',
                'email' => 'newadmin@bim.com',
                'role' => Role::Admin,
            ])
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', __('User does not have the right permissions.'));
    }

    public function test_admin_controller_update_without_first_name_and_last_name_unsuccessful()
    {
        $newSuperAdminUser = $this->createAdmin('newadmin@bim.com');

        $this->actingAs(self::$superAdminUser)
            ->putJson('api/v1/admin/admins/'.$newSuperAdminUser->id, [
                'email' => 'newadmin@bim.com',
                'role' => Role::Admin,
            ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('first_name')
            ->assertJsonValidationErrorFor('last_name');
    }

    public function test_admin_controller_update_with_email_already_exists_unsuccessful()
    {
        $newSuperAdminUser = $this->createAdmin('newadmin@bim.com');

        $this->actingAs(self::$superAdminUser)
            ->putJson('api/v1/admin/admins/'.$newSuperAdminUser->id, [
                'first_name' => 'admin',
                'last_name' => 'admin',
                'email' => 'admin@bim.com',
                'role' => Role::Admin,
            ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('email');
    }

    public function test_admin_controller_update_without_email_unsuccessful()
    {
        $newSuperAdminUser = $this->createAdmin('newadmin@bim.com');

        $this->actingAs(self::$superAdminUser)
            ->putJson('api/v1/admin/admins/'.$newSuperAdminUser->id, [
                'first_name' => 'admin',
                'last_name' => 'admin',
                'role' => Role::Admin,
            ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('email');
    }
}
