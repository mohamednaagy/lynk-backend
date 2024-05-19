<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Commodity;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\User;
use App\Transformers\CommoditySuppliersTransformer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;
use Tests\Traits\InteractsWithCommoditySupplier;

class CommoditySupplierControllerIndexTest extends TestCase
{
    use AssertsAccessByRoleAndArea, InteractsWithCommoditySupplier , RefreshDatabase;

    private static User $userAdmin;

    private static User $userManager;

    private static $suppliers;

    private $endpoint = 'api/v1/admin/commodity-suppliers';

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$userAdmin = $this->createSuperAdminUser();
        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        self::$suppliers = $this->getCommoditySupplier(5, true);

        $this->assignPermissionToUser(
            self::$userManager,
            perm(Area::SuperAdmin, [Subject::Lenders, Action::Index])
        );
    }

    public function test_un_auth_user_cant_index_commodity_suppliers(): void
    {
        $this->getJson($this->endpoint)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_admin_can_index_commodity_suppliers_successfully(): void
    {

        $this->actingAs(self::$userAdmin)
            ->getJson($this->endpoint)
            ->assertOk()
            ->assertExactJson(
                fractal(self::$suppliers, new CommoditySuppliersTransformer())
                    ->parseIncludes([
                        'id',
                        'legal_name',
                        'unique_name',
                        'market_type',
                        'status',
                        'created_at',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_manager_without_permissions_cant_index_lenders(): void
    {
        $this->actingAs(self::$userManager)
            ->getJson($this->endpoint)
            ->assertForbidden();
    }
}
