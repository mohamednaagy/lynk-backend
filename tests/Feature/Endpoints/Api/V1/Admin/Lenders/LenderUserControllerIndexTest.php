<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use App\Transformers\UserTransformer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class LenderUserControllerIndexTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany;

    private static Company $lender;

    private static Wallet $wallet;

    private static User $userAdmin;

    private static User $userManager;

    private static LengthAwarePaginator $users;

    /**
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$lender, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userAdmin = $this->createSuperAdminUser();
        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(self::$userManager, perm(Area::SuperAdmin, [Subject::LenderUsers, Action::Index]));

        self::$users = self::$lender->users()->whereHas('roles', function ($query) {
            return $query->whereIn('name', [
                Role::LenderAdmin,
                Role::LenderOrderCreator,
                Role::LenderBilling,
                Role::LenderSupervisor,
            ]);
        })->where('company_id', self::$lender->id)
            ->withCount('orders')
            ->withCount('orders')
            ->with('permissions', 'roles')
            ->paginate();
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_index_lender_users(): void
    {
        $this->getJson('api/v1/admin/lenders/'.self::$lender->id.'/users')
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_admin_user_can_index_lender_users(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson('api/v1/admin/lenders/'.self::$lender->id.'/users')
            ->assertOk()
            ->assertExactJson(
                fractal(self::$users, new UserTransformer(Area::Lender))
                    ->parseIncludes([
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'phone_number',
                        'phone_country_code',
                        'formatted_phone_number',
                        'orders_count',
                        'role',
                    ])->respond()
                    ->getData(true)
            );
    }

    /**
     * @return void
     */
    public function test_that_auth_manager_user_without_permissions_cant_index_lender_users(): void
    {
        Grantify::syncPermissionToModel(self::$userManager, []);

        $this->actingAs(self::$userManager)
            ->getJson('api/v1/admin/lenders/'.self::$lender->id.'/users')
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_that_auth_manager_user_can_index_lender_users(): void
    {
        $this->actingAs(self::$userManager)
            ->getJson('api/v1/admin/lenders/'.self::$lender->id.'/users')
            ->assertOk()
            ->assertExactJson(
                fractal(self::$users, new UserTransformer(Area::Lender))
                    ->parseIncludes([
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'phone_number',
                        'phone_country_code',
                        'formatted_phone_number',
                        'orders_count',
                        'role',
                    ])->respond()
                    ->getData(true)
            );
    }
}
