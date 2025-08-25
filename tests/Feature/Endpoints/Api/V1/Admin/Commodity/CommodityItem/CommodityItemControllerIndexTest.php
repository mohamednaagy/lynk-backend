<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Commodity\CommodityItem;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\CommodityTypeStatus;
use App\Enums\LocalMarket\SupplierStatus;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\User;
use App\Transformers\Admin\CommodityItem\CommodityItemsTransformer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;
use Tests\Traits\InteractsWithCommodityItem;
use Tests\Traits\InteractsWithSupplier;

class CommodityItemControllerIndexTest extends TestCase
{
    use AssertsAccessByRoleAndArea, InteractsWithCommodityItem, InteractsWithSupplier, RefreshDatabase;

    private static User $userAdmin;

    private static User $userManager;

    private static $commodityItems;

    private const NUMBER_OF_ACTIVE_COMMODITY_ITEMS = 5;

    private $endpoint = 'api/v1/admin/commodity-items';

    /**
     * @throws BindingResolutionException
     */
    protected function setUp(): void
    {
        parent::setUp();
        self::$userAdmin = $this->createSuperAdminUser();
        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        self::$commodityItems = $this->getAdminCommodityItems(self::NUMBER_OF_ACTIVE_COMMODITY_ITEMS, true);
        $this->assignPermissionToUser(
            self::$userManager,
            perm(Area::CommoditySupplier, [Subject::CommoditySupplierItems, Action::Index])
        );

    }

    public function test_un_auth_user_cant_index_commodity_items(): void
    {
        $this->getJson($this->endpoint)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_admin_can_index_commodity_items_successfully(): void
    {

        $this->actingAs(self::$userAdmin)
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
                        'supplier',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_manager_without_permissions_cant_index_items(): void
    {
        $this->actingAs(self::$userManager)
            ->getJson($this->endpoint)
            ->assertForbidden();
    }

    public function test_filter_commodity_items_by_active_status(): void
    {
        // Create active supplier
        $activeSupplier = $this->createSupplier(commodityStatus: SupplierStatus::Active);

        // Create inactive supplier
        $inactiveSupplier = $this->createSupplier(commodityStatus: SupplierStatus::Inactive);

        // Create inactive commodity type
        $inactiveCommodityType = $this->createCommodityType(status: CommodityTypeStatus::Inactive);

        // Create inactive commodity item by assigning inactive supplier
        $this->createCommodityItem($inactiveSupplier, unique_name: 'inactive commodity item');

        // Create inactive commodity item by assigning inactive commodity type
        $this->createCommodityItem($activeSupplier, commodityType: $inactiveCommodityType);

        // Create inactive commodity item by assigning both inactive supplier and inactive commodity type
        $this->createCommodityItem($inactiveSupplier, commodityType: $inactiveCommodityType);

        // Total inactive commodity items in this test
        $inactiveCommodityItemsCount = 3;

        /**
         * In this scenario, we have some active commodity items initialized in the setUp function,
         * and some inactive commodity items initialized here in this test.
         */

        // [1] Assert that when the "active" filter is not provided, the default is "all" (3),
        // meaning all active/inactive commodity items should be returned.
        $allCommodityItemsCount = $inactiveCommodityItemsCount + self::NUMBER_OF_ACTIVE_COMMODITY_ITEMS;
        $this->actingAs(self::$userAdmin)
            ->getJson($this->endpoint)
            ->assertOk()
            ->assertJsonCount($allCommodityItemsCount, 'data');

        // [2] Assert that when we set the "active" filter to be 1, we will get only active commodity items.
        $response = $this->actingAs(self::$userAdmin)
            ->getJson(sprintf('%s?active=1', $this->endpoint))
            ->assertOk()
            ->assertJsonCount(self::NUMBER_OF_ACTIVE_COMMODITY_ITEMS, 'data');

        $responseData = $response->json();
        $data = $responseData['data'];
        foreach ($data as $commodityItem) {
            $commodityTypeStatus = $commodityItem['commodity_type']['status']['value'];
            $supplierStatus = $commodityItem['supplier']['status']['value'];

            $this->assertTrue(
                $commodityTypeStatus == CommodityTypeStatus::Active && $supplierStatus == SupplierStatus::Active,
                'Both commodity_type.status.value and supplier.status.value must be 1 "active"'
            );
        }

        // [3] Assert that when we set the "active" filter to be 2, we will get only inactive commodity items.
        $response = $this->actingAs(self::$userAdmin)
            ->getJson(sprintf('%s?active=2', $this->endpoint))
            ->assertOk()
            ->assertJsonCount($inactiveCommodityItemsCount, 'data');

        $responseData = $response->json();
        $data = $responseData['data'];
        foreach ($data as $commodityItem) {
            $commodityTypeStatus = $commodityItem['commodity_type']['status']['value'];
            $supplierStatus = $commodityItem['supplier']['status']['value'];

            $this->assertTrue(
                $commodityTypeStatus == CommodityTypeStatus::Inactive || $supplierStatus == SupplierStatus::Inactive,
                'Either commodity_type.status.value or supplier.status.value must be 2 "inactive"'
            );
        }

        // [4] Assert that when we set the "active" filter to be 3, we will get both active and inactive commodity items.
        $this->actingAs(self::$userAdmin)
            ->getJson(sprintf('%s?active=3', $this->endpoint))
            ->assertOk()
            ->assertJsonCount($allCommodityItemsCount, 'data');
    }
}
