<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin;

use App\Actions\Contracts\GetPaginatedUsersByRole;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Models\User;
use App\Transformers\UserTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Grantify\Facades\Grantify;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithAdmin;

class AdminControllerIndexTest extends TestCase
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
            perm(Area::SuperAdmin, [Subject::Admins, Action::Index]),
        );
    }

    public function test_un_auth_cant_index_admins()
    {
        $this->getJson('api/v1/admin/admins')
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
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

    public function test_admin_controller_index_with_manager_no_permissions_failed()
    {
        Grantify::syncPermissionToModel(self::$managerAdminUser, []);

        $this->actingAs(self::$managerAdminUser)
            ->getJson('api/v1/admin/admins')
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', __('User does not have the right permissions.'));
    }
}
