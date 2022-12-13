<?php

namespace Endpoints\Api\V1\Admin;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Mail\Admin\CompleteAdminRegisterInvitation;
use App\Models\User;
use App\Transformers\UserTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Grantify\Facades\Grantify;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithAdmin;

class AdminControllerStoreTest extends TestCase
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
            perm(Area::SuperAdmin, [Subject::Admins, Action::Create]),
        );
    }

    public function test_admin_controller_create_with_super_admin_success()
    {
        Mail::fake();

        $response = $this->actingAs(self::$superAdminUser)
            ->postJson('api/v1/admin/admins', [
                'first_name' => 'admin',
                'last_name' => 'admin',
                'email' => 'newadmin@bim.com',
                'role' => Role::Admin,
                'redirect_url' => 'http://localhost:8000/api/v1/admin/sign-up',
            ])
            ->assertStatus(Response::HTTP_CREATED);

        $newSuperAdminUser = User::find($response->json('data.id'));
        $newSuperAdminUser->load('permissions', 'roles');

        $response->assertExactJson(
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

        Mail::assertSent(CompleteAdminRegisterInvitation::class);
    }

    public function test_admin_controller_create_with_manager_success()
    {
        Mail::fake();

        $response = $this->actingAs(self::$managerAdminUser)
            ->postJson('api/v1/admin/admins', [
                'first_name' => 'admin',
                'last_name' => 'admin',
                'email' => 'newadmin@bim.com',
                'role' => Role::Admin,
                'redirect_url' => 'http://localhost:8000/api/v1/admin/sign-up',
            ])
            ->assertStatus(Response::HTTP_CREATED);

        $newManagerAdminUser = User::find($response->json('data.id'));
        $newManagerAdminUser->load('permissions', 'roles');

        $response->assertExactJson(
            fractal($newManagerAdminUser, new UserTransformer(Area::SuperAdmin))
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

        Mail::assertSent(CompleteAdminRegisterInvitation::class);
    }

    public function test_admin_controller_create_with_manager_no_permissions_unsuccessful()
    {
        Grantify::syncPermissionToModel(self::$managerAdminUser, []);

        $this->actingAs(self::$managerAdminUser)
            ->postJson('api/v1/admin/admins', [
                'first_name' => 'admin',
                'last_name' => 'admin',
                'email' => 'newadmin@bim.com',
                'role' => Role::Admin,
                'redirect_url' => 'http://localhost:8000/api/v1/admin/sign-up',
            ])
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', __('User does not have the right permissions.'));
    }

    public function test_admin_controller_create_without_first_name_and_last_name_unsuccessful()
    {
        $this->actingAs(self::$superAdminUser)
            ->postJson('api/v1/admin/admins', [
                'email' => 'newadmin@bim.com',
                'role' => Role::Admin,
            ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('first_name')
            ->assertJsonValidationErrorFor('last_name');
    }

    public function test_admin_controller_create_with_email_already_exists_unsuccessful()
    {
        $this->actingAs(self::$superAdminUser)
            ->postJson('api/v1/admin/admins', [
                'first_name' => 'admin',
                'last_name' => 'admin',
                'email' => 'admin@bim.com',
                'role' => Role::Admin,
            ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('email');
    }
}
