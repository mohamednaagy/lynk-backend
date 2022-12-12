<?php

namespace Endpoints\Api\V1\Admin;

use App\Actions\Contracts\GetPaginatedUsersByRole;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Mail\Admin\CompleteAdminRegisterInvitation;
use App\Models\User;
use App\Transformers\UserTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithAdmin;

class AdminControllerTest extends TestCase
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
            [
                perm(Area::SuperAdmin, [Subject::Admins, Action::Index]),
                perm(Area::SuperAdmin, [Subject::Admins, Action::Create]),
                perm(Area::SuperAdmin, [Subject::Admins, Action::Show]),
                perm(Area::SuperAdmin, [Subject::Admins, Action::Edit]),
                perm(Area::SuperAdmin, [Subject::Admins, Action::Delete]),
            ]
        );
    }

    public function test_admin_controller_index_with_super_admin_success()
    {
        $admins = app(GetPaginatedUsersByRole::class)->handle(Area::roles(Area::SuperAdmin));

        $this->actingAs(self::$superAdminUser)
            ->getJson('api/v1/admin/admins')
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal($admins, new UserTransformer(Area::SuperAdmin))
                    ->parseIncludes([
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'role',
                        'phone_number',
                        'phone_country_code',
                        'formatted_phone_number',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_admin_controller_index_with_manager_success()
    {
        $admins = app(GetPaginatedUsersByRole::class)->handle(Area::roles(Area::SuperAdmin));

        $this->actingAs(self::$managerAdminUser)
            ->getJson('api/v1/admin/admins')
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal($admins, new UserTransformer(Area::SuperAdmin))
                    ->parseIncludes([
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'role',
                        'phone_number',
                        'phone_country_code',
                        'formatted_phone_number',
                    ])
                    ->respond()
                    ->getData(true)
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

    public function test_admin_controller_show_with_super_admin_success()
    {
        $newSuperAdminUser = $this->createAdmin('newadmin@bim.com');

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
        $newManagerUser = $this->createManager('newmanager@bim.com');

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

    public function test_admin_controller_delete_with_super_admin_success()
    {
        $newSuperAdminUser = $this->createAdmin('newadmin@bim.com');

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

    public function test_admin_controller_delete_with_manager_success()
    {
        $newSuperAdminUser = $this->createManager('newadmin@bim.com');

        $this->actingAs(self::$managerAdminUser)
            ->deleteJson('api/v1/admin/admins/'.$newSuperAdminUser->id)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['data']);

        $this->actingAs(self::$managerAdminUser)
            ->postJson('api/v1/admin/admins', [
                'first_name' => 'admin',
                'last_name' => 'admin',
                'email' => 'newadmin@bim.com',
                'role' => Role::Manager,
                'redirect_url' => 'http://localhost:8000/api/v1/admin/sign-up',
            ])
            ->assertStatus(Response::HTTP_CREATED);
    }
}
