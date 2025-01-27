<?php

namespace Tests\Feature\Endpoints\Api\V1\Supplier\CommodityItem;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\CommodityTypeStatus;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\User;
use App\Transformers\Supplier\CommodityItem\CommodityItemsTransformer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;
use Tests\Traits\InteractsWithCommodityItem;
use Tests\Traits\InteractsWithSupplier;

class CommodityItemControllerIndexTest extends TestCase
{
    use AssertsAccessByRoleAndArea, InteractsWithCommodityItem ,  InteractsWithSupplier, RefreshDatabase;

    private static User $supplierAdmin;

    private static User $userManager;

    private static $commodityItems;

    private static $supplier;

    private $endpoint = 'api/v1/supplier/commodity-items';

    private const NUMBER_OF_ACTIVE_COMMODITY_ITEMS = 5;

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();
        self::$supplier = $this->createSupplier();

        self::$supplierAdmin = $this->createSupplierUser(
            self::$supplier->id,
            Role::SupplierAdmin,
            [
                'email_verified_at' => now(),
            ]
        );
        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        self::$commodityItems = $this->getCommodityItems(self::$supplier, self::NUMBER_OF_ACTIVE_COMMODITY_ITEMS, true);

        $this->assignPermissionToUser(
            self::$userManager,
            perm(Area::CommoditySupplier, [Subject::CommoditySupplierItems, Action::Index])
        );

    }

    public function test_un_auth_user_cant_index_commodity_items(): void
    {
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->getJson($this->endpoint)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_admin_can_index_commodity_items_successfully(): void
    {

        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->getJson($this->endpoint)
            ->assertOk()
            ->assertExactJson(
                fractal(self::$commodityItems, new CommodityItemsTransformer)
                    ->parseIncludes([
                        'id',
                        'name',
                        'unique_name',
                        'commodity_type',
                        'max_price',
                        'available_units',
                        'reserved_units',
                        'created_at',
                        'is_deletable',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_manager_without_permissions_cant_index_items(): void
    {
        $this->actingAs(self::$userManager)
            ->withHeader('X-Company', self::$supplier->id)
            ->getJson($this->endpoint)
            ->assertForbidden();
    }

    public function test_filter_commodity_items_by_active_status(): void
    {
        // Create inactive commodity type
        $inactiveCommodityType = $this->createCommodityType(status: CommodityTypeStatus::Inactive);

        // Create inactive commodity item by assigning inactive commodity type
        $this->createCommodityItem(self::$supplier, commodityType: $inactiveCommodityType);

        // Total inactive commodity items in this test
        $inactiveCommodityItemsCount = 1;

        /**
         * In this scenario, we have some active commodity items initialized in the setUp function,
         * and some inactive commodity items initialized here in this test.
         */

        // [1] Assert that when the "active" filter is not provided, the default is "all" (3),
        // meaning both active/inactive commodity items should be returned.
        $allCommodityItemsCount = $inactiveCommodityItemsCount + self::NUMBER_OF_ACTIVE_COMMODITY_ITEMS;
        $this->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->getJson($this->endpoint)
            ->assertOk()
            ->assertJsonCount($allCommodityItemsCount, 'data');

        // [2] Assert that when we set the "active" filter to be 1, we will get only active commodity items.
        $response = $this->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->getJson(sprintf('%s?active=1', $this->endpoint))
            ->assertOk()
            ->assertJsonCount(self::NUMBER_OF_ACTIVE_COMMODITY_ITEMS, 'data');

        $responseData = $response->json();
        $data = $responseData['data'];
        foreach ($data as $commodityItem) {
            $commodityTypeStatus = $commodityItem['commodity_type']['status']['value'];

            $this->assertEquals($commodityTypeStatus, CommodityTypeStatus::Active);
        }

        // [3] Assert that when we set the "active" filter to be 2, we will get only inactive commodity items.
        $response = $this->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->getJson(sprintf('%s?active=2', $this->endpoint))
            ->assertOk()
            ->assertJsonCount($inactiveCommodityItemsCount, 'data');

        $responseData = $response->json();
        $data = $responseData['data'];
        foreach ($data as $commodityItem) {
            $commodityTypeStatus = $commodityItem['commodity_type']['status']['value'];

            $this->assertEquals($commodityTypeStatus, CommodityTypeStatus::Inactive);
        }

        // [4] Assert that when we set the "active" filter to be 3, we will get both active and inactive commodity items.
        $this->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->getJson(sprintf('%s?active=3', $this->endpoint))
            ->assertOk()
            ->assertJsonCount($allCommodityItemsCount, 'data');
    }
}
