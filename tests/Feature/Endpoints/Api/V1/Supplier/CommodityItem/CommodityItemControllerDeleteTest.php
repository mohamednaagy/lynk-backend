<?php

namespace Endpoints\Api\V1\Supplier\CommodityItem\Inventory;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Jobs\LocalMarket\DeleteCommodityItem;
use App\Jobs\LocalMarket\UpdateInventoryStock;
use App\Models\CommodityItem;
use App\Models\User;
use App\Observers\LocalMarketInventoryObserver;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;
use Tests\Traits\InteractsWithCommodityInventory;
use Tests\Traits\InteractsWithCommodityItem;
use Tests\Traits\InteractsWithSupplier;

class CommodityItemControllerDeleteTest extends TestCase
{
    use AssertsAccessByRoleAndArea, InteractsWithCommodityInventory, InteractsWithCommodityItem, InteractsWithSupplier, RefreshDatabase;

    private static User $supplierAdmin;

    private static User $supplierAdmin2;

    private static User $userManager;

    private static $commodityItems;

    private static $location;

    private static $supplier;

    private static $supplier2;

    private static string $endpoint;

    private static array $commodityItem;

    private static $inventory;

    private static $inventory2;

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        self::$supplier = $this->createSupplier();
        self::$supplier2 = $this->createSupplier();

        // self::$commodityItems = $this->createCommodityItem(
        //     self::$supplier,
        //     'name'.rand(11, 999),
        //     'unique name'.rand(11, 999),
        //     'Test Description',
        //     10,
        //     20,
        //     10,
        //     $this->createCurrency()->id,
        //     $this->createMeasurement()->id,
        //     $this->createCommodityType('type', 'test_item')->id,
        // );

        self::$inventory = $this->createInventory(
            self::$supplier,
            300
        );

        $observer = new LocalMarketInventoryObserver();
        $observer->created(self::$inventory);

        $job = new UpdateInventoryStock(self::$inventory, 300, true);
        Bus::dispatchNow($job);

        self::$location = $this->createSupplierLocation(
            self::$supplier,
            'name' . rand(11, 999),
            'unique name' . rand(11, 999),
            'Test Description',
        );

        self::$supplierAdmin = $this->createSupplierUser(
            self::$supplier->id,
            Role::SupplierAdmin,
            [
                'email_verified_at' => now(),
            ]
        );
        self::$supplierAdmin2 = $this->createSupplierUser(
            self::$supplier2->id,
            Role::SupplierAdmin,
            [
                'email_verified_at' => now(),
            ]
        );
        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(
            self::$userManager,
            perm(Area::CommoditySupplier, [Subject::CommoditySupplierItems, Action::Delete])
        );

        self::$endpoint = 'api/v1/supplier/commodity-items/' . self::$inventory->commodity_item_id;

        self::$inventory2 = [
            'location_id' => self::$location->id,
            'total_units' => 20,
        ];
    }

    public function test_un_auth_user_cant_delete_commodity_item(): void
    {
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->deleteJson(self::$endpoint)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_supplier_user_cant_delete_commodity_item_with_active_reserved_units(): void
    {
        // Set up inventory with reserved items
        $inventory = self::$inventory;
        $inventory->reserved_items = 50;
        $inventory->save();

        // Attempt to delete the commodity item
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->deleteJson(self::$endpoint)
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertExactJson([
                'message' => 'Reserved Units is greater than 0. Commodity Item cannot be deleted.',
                'code' => 1042,
            ]);
    }

    public function test_supplier_user_can_delete_commodity_item_successfully(): void
    {
        // Attempt to delete the commodity item
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->deleteJson(self::$endpoint)
            ->assertStatus(Response::HTTP_OK);

        // Manually dispatch the job immediately
        $job = new DeleteCommodityItem(CommodityItem::find(self::$inventory->commodity_item_id));
        Bus::dispatchNow($job);

        // Assert the inventory is soft deleted
        $this->assertDatabaseMissing('local_market_inventories', [
            'id' => self::$inventory->id,
            'deleted_at' => null,
        ]);
    }

    public function test_delete_commodity_item_job_is_fired(): void
    {
        // Mock the queue
        Queue::fake();

        // Attempt to delete the commodity item
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->deleteJson(self::$endpoint)
            ->assertStatus(Response::HTTP_OK);

        // Assert the job was pushed to the queue
        Queue::assertPushed(DeleteCommodityItem::class);
    }

    public function test_inventory_units_are_soft_deleted(): void
    {
        // Attempt to delete the commodity item
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->deleteJson(self::$endpoint)
            ->assertStatus(Response::HTTP_OK);

        // Manually dispatch the job immediately
        $job = new DeleteCommodityItem(CommodityItem::find(self::$inventory->commodity_item_id));
        Bus::dispatchNow($job);

        // Assert the inventory units are soft deleted
        $this->assertSoftDeleted('local_market_inventory_units', [
            'local_market_inventory_id' => self::$inventory->id,
        ]);
    }
}
