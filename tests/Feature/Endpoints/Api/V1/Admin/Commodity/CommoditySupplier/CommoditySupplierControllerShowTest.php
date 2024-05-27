<?php

namespace Endpoints\Api\V1\Admin\Commodity\CommoditySupplier;

use App\Enums\Role;
use App\Models\User;
use App\Transformers\CommoditySuppliersTransformer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;
use Tests\Traits\InteractsWithCommoditySupplier;

class CommoditySupplierControllerShowTest extends TestCase
{
    use AssertsAccessByRoleAndArea, InteractsWithCommoditySupplier , RefreshDatabase;

    private static User $userAdmin;

    private static User $userManager;

    private static $supplier;

    private $endpoint = 'api/v1/admin/commodity-suppliers';

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$userAdmin = $this->createSuperAdminUser();

        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        self::$supplier = $this->createCommoditySupplier();
        $this->endpoint = 'api/v1/admin/commodity-suppliers/'.self::$supplier->id;
    }

    public function test_un_auth_user_cant_show_commodity_supplier(): void
    {
        $this->getJson($this->endpoint)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_admin_user_can_show_commodity_supplier_successfully(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson($this->endpoint)
            ->assertOk()
            ->assertJson(
                fractal(self::$supplier->company, new CommoditySuppliersTransformer())
                    ->parseIncludes([
                        'id',
                        'legal_name',
                        'description',
                        'unique_name',
                        'market_type',
                        'status',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_manager_without_permissions_cant_show_commodity_supplier(): void
    {
        Grantify::syncPermissionToModel(self::$userManager, []);

        $this->actingAs(self::$userManager)
            ->getJson($this->endpoint)
            ->assertForbidden();
    }
}
