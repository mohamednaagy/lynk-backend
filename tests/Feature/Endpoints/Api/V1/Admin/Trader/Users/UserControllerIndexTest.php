<?php

namespace Endpoints\Api\V1\Admin\Trader\Users;

use App\Enums\Area;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use App\Transformers\UserTransformer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class UserControllerIndexTest extends TestCase
{
    use RefreshDatabase, InteractsWithCompany, InteractsWithUser, AssertsAccessByRoleAndArea;

    private static User $userAdmin;

    private static Company $company;

    private static Wallet $wallet;

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
        [self::$company, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userTraderAdmin = $this->createTraderUser(self::$company->id);
        self::$traderUsersCollection = self::$company->users()->whereHas('roles', function ($query) {
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
        $this->getJson('api/v1/admin/traders/'.self::$company->id.'/users')
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
            ->getJson('api/v1/admin/traders/'.self::$company->id.'/users')
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
                    ])->respond()
                    ->getData(true)
            );
    }

    /**
     * @return void
     */
    public function test_user_roles_can_index_trader_users(): void
    {
        $this->assertStatusCodeForAreaRoles(200, Area::SuperAdmin, function ($user, $role) {
            return $this->actingAs(self::$userAdmin)
                ->getJson('api/v1/admin/traders/'.self::$company->id.'/users')->assertExactJson(
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
                        ])->respond()
                        ->getData(true)
                );
        });
    }
}
