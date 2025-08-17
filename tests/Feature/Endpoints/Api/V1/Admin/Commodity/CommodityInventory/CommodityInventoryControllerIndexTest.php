<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Commodity\CommodityInventory;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Models\User;
use App\Transformers\LocalMarketInventoryTransformer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;
use Tests\Traits\InteractsWithCommodityInventory;
use Tests\Traits\InteractsWithCommodityItem;
use Tests\Traits\InteractsWithSupplier;

class CommodityInventoryControllerIndexTest extends TestCase
{
    use AssertsAccessByRoleAndArea, InteractsWithCommodityInventory, InteractsWithCommodityItem ,  InteractsWithSupplier, RefreshDatabase;

    private static User $userAdmin;

    private static $commodityItems;

    private static $commodityInventory;

    private static $supplier;

    private static $endpoint;

    /**
     * @throws BindingResolutionException
     */
    protected function setUp(): void
    {
        parent::setUp();
        self::$supplier = $this->createSupplier();
        self::$commodityItems = $this->createCommodityItem(
            self::$supplier,
            'name'.rand(11, 999),
            'unique name'.rand(11, 999),
            'Test Description',
            10,
            20,
            10,
            $this->createCurrency()->id,
            $this->createMeasurement()->id,
            $this->createCommodityType('type', 'test_item')->id,
        );
        self::$userAdmin = $this->createSuperAdminUser();
        self::$commodityInventory = $this->getCommodityInventories(self::$supplier, self::$commodityItems, 5, true);

        self::$endpoint = 'api/v1/admin/commodity-items/'.self::$commodityItems->id.'/inventories';

        $this->assignPermissionToUser(
            self::$userAdmin,
            perm(Area::CommoditySupplier, [Subject::CommoditySupplierInventories, Action::Index])
        );

    }

    public function test_un_auth_user_cant_index_commodity_inventories(): void
    {
        $this
            ->getJson(self::$endpoint)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_admin_can_index_commodity_inventories_successfully(): void
    {

        $this
            ->actingAs(self::$userAdmin)
            ->getJson(self::$endpoint)
            ->assertOk()
            ->assertExactJson(
                fractal(self::$commodityInventory, new LocalMarketInventoryTransformer)
                    ->parseIncludes([
                        'id',
                        'company_id',
                        'company_name',
                        'commodity_item_id',
                        'commodity_item',
                        'commodity_type',
                        'min_price',
                        'max_price',
                        'supplier_location_id',
                        'supplier_location',
                        'total_items',
                        'available_quantity',
                        'reserved_items',
                        'status',
                        'is_editable',
                        'is_deletable',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }
}
