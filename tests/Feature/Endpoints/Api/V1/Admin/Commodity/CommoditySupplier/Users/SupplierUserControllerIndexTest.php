<?php

namespace Endpoints\Api\V1\Admin\Commodity\CommoditySupplier\Users;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\CommoditySupplier;
use App\Models\User;
use App\Transformers\UserTransformer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\InteractsWithCommoditySupplier;
use Tests\Traits\InteractsWithUser;

class SupplierUserControllerIndexTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCommoditySupplier;

    private static CommoditySupplier $supplier;

    private static User $userAdmin;

    private static LengthAwarePaginator $users;

    private static string $endpoint;

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$supplier = $this->createCommoditySupplier(
            'new legal name'.rand(11, 999),
            'new unique name'.rand(11, 999),
            '1001280070'
        );
        self::$userAdmin = $this->createSuperAdminUser();
        $this->assignPermissionToUser(self::$userAdmin, perm(Area::SuperAdmin, [Subject::CommoditySupplierUsers, Action::Index]));
        self::$endpoint = 'api/v1/admin/commodity-suppliers/'.self::$supplier->id.'/users';
        self::$users = self::$supplier->users()->whereHas('roles', function ($query) {
            return $query->whereIn('name', [
                Role::SupplierAdmin,
                Role::SupplierApiAdmin,
            ]);
        })->with('permissions', 'roles')
            ->paginate();
    }

    public function test_that_un_auth_user_cant_index_supplier_users(): void
    {
        $this->getJson(self::$endpoint)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_that_auth_admin_user_can_index_supplier_users(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson(self::$endpoint)
            ->assertOk()
            ->assertExactJson(
                fractal(self::$users, new UserTransformer(Area::CommoditySupplier))
                    ->parseIncludes([
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'phone_number',
                        'phone_country_code',
                        'formatted_phone_number',
                        'role',
                        'is_invitation_accepted',
                    ])->respond()
                    ->getData(true)
            );
    }
}
