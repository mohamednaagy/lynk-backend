<?php

namespace Endpoints\Api\V1\Supplier\CommodityItem;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\CommodityItem;
use App\Models\User;
use App\Transformers\Supplier\CommodityItem\CommodityItemsTransformer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Grantify\Facades\Grantify;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;
use Tests\Traits\InteractsWithCommodityItem;
use Tests\Traits\InteractsWithSupplier;

class CommodityItemControllerShowTest extends TestCase
{
    use AssertsAccessByRoleAndArea, InteractsWithCommodityItem ,  InteractsWithSupplier, RefreshDatabase;

    private static User $supplierAdmin;

    private static User $userManager;

    private static $commodityItems;

    private static $supplier;

    private static $supplier2;

    private $endpoint = 'api/v1/supplier/commodity-items';

    private static CommodityItem $item;

    private static CommodityItem $item2;

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();
        self::$supplier = $this->createSupplier();
        self::$supplier2 = $this->createSupplier();

        self::$supplierAdmin = $this->createSupplierUser(
            self::$supplier->id,
            Role::SupplierAdmin,
            [
                'email_verified_at' => now(),
            ]
        );
        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        self::$commodityItems = $this->getCommodityItems(self::$supplier, 5, true);
        self::$item = $this->createCommodityItem(self::$supplier);
        self::$item2 = $this->createCommodityItem(self::$supplier2, 'test_update2', 'test_update_unique2');

        $this->assignPermissionToUser(
            self::$userManager,
            perm(Area::CommoditySupplier, [Subject::CommoditySupplierItems, Action::Index])
        );

        $this->endpoint = $this->endpoint.'/'.self::$item->id;

    }

    public function test_un_auth_user_cant_show_commodity_item(): void
    {
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->getJson($this->endpoint)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_supplier_admin_user_can_show_commodity_item_successfully(): void
    {
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->getJson($this->endpoint)
            ->assertOk()
            ->assertJson(
                fractal(self::$item, new CommodityItemsTransformer())
                    ->parseIncludes([
                        'id',
                        'name',
                        'unique_name',
                        'commodity_type',
                        'description',
                        'min_price',
                        'max_price',
                        'volume_sellable_unit',
                        'currency',
                        'measurement',
                        'available_units',
                        'reserved_units',
                        'created_at',
                        'is_deletable',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_supplier_user_show_item_related_to_another_supplier(): void
    {
        $endpoint2 = 'api/v1/supplier/commodity-items/'.self::$item2->id;

        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->getJson($endpoint2)
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function test_manager_without_permissions_cant_show_commodity_item(): void
    {
        Grantify::syncPermissionToModel(self::$userManager, []);

        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$userManager)
            ->getJson($this->endpoint)
            ->assertForbidden();
    }
}
