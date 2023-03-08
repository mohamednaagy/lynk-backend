<?php

namespace Endpoints\Api\V1\Admin\Traders\Users;

use App\Enums\Area;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Transformers\UserTransformer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class UserControllerIndexTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    private static User $userAdmin;

    private static Company $traderCompany;

    private static User $userTraderAdmin;

    private static LengthAwarePaginator $traderUsersCollection;

    /**
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$userAdmin = $this->createSuperAdminUser();
        [self::$traderCompany] = $this->createTraderCompany('2000');
        self::$userTraderAdmin = $this->createTraderUser(self::$traderCompany->id);

        [$secondTraderCompany] = $this->createTraderCompany('2000');
        $this->createTraderUser($secondTraderCompany->id);

        [$lenderCompany] = $this->createLenderCompany('2000');
        $this->createLenderUser($lenderCompany->id);

        self::$traderUsersCollection = self::$traderCompany->users()->whereHas('roles', function ($query) {
            return $query->whereIn('name', [
                Role::TraderAdmin,
            ]);
        })->paginate();
    }

    /**
     * @return void
     */
    public function test_un_auth_user_cant_index_trader_users(): void
    {
        $this->getJson('api/v1/admin/traders/'.self::$traderCompany->id.'/users')
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_admin_user_can_index_trader_users(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson('api/v1/admin/traders/'.self::$traderCompany->id.'/users')
            ->assertOk()
            ->assertExactJson(
                fractal(self::$traderUsersCollection, new UserTransformer(Area::Trader))
                    ->parseIncludes([
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'phone_number',
                        'phone_country_code',
                        'formatted_phone_number',
                        'role',
                        'is_active',
                        'is_invitation_accepted',
                    ])->respond()
                    ->getData(true)
            );
    }

    /**
     * @return void
     */
    public function test_only_super_admin_roles_can_index_trader_users(): void
    {
        $this->assertStatusCodeForAllRolesExceptForArea(403, [Area::SuperAdmin], function ($user, $role) {
            return $this->actingAs($user)
                ->getJson('api/v1/admin/traders/'.self::$traderCompany->id.'/users');
        });
    }
}
