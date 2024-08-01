<?php

namespace Endpoints\Api\V1\Supplier\CommodityItem\Inventory;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Jobs\LocalMarket\DeleteInventoryStock;
use App\Jobs\LocalMarket\UpdateInventoryStock;
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

class InventoryControllerDeleteTest extends TestCase
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
            'name'.rand(11, 999),
            'unique name'.rand(11, 999),
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
            perm(Area::CommoditySupplier, [Subject::CommoditySupplierInventories, Action::Delete])
        );

        self::$endpoint = 'api/v1/supplier/commodity-items/'.self::$commodityItems->id.'/inventory/'.self::$inventory->id;

        self::$inventory2 = [
            'location_id' => self::$location->id,
            'total_units' => 20,
        ];

    }

    public function test_un_auth_user_cant_delete_commodity_inventory(): void
    {
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->deleteJson(self::$endpoint)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_supplier_user_cant_delete_commodity_inventory_with_active_reserved_units(): void
    {
        $inventory = self::$inventory;
        $inventory->reserved_items = 50;
        $inventory->save();

        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->deleteJson(self::$endpoint)
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertExactJson([
                'message' => 'Reserved Units is greater than 0. Commodity Inventory cannot be deleted.',
                'code' => 1040,
            ]);
    }

    public function test_supplier_user_can_delete_commodity_inventory_successfully(): void
    {
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->deleteJson(self::$endpoint)
            ->assertStatus(Response::HTTP_OK);

        // Manually dispatch the job immediately
        $job = new DeleteInventoryStock(self::$inventory);
        Bus::dispatchNow($job);

        $this->assertDatabaseMissing('local_market_inventories', [
            'id' => self::$inventory->id,
            'deleted_at' => null,
        ]);
    }

    public function test_delete_inventory_stock_job_is_fired() {
        $this
        ->withHeader('X-Company', self::$supplier->id)
        ->actingAs(self::$supplierAdmin)
        ->deleteJson(self::$endpoint)
        ->assertStatus(Response::HTTP_OK);

        Queue::assertPushed(DeleteInventoryStock::class);
    }

    public function test_inventory_units_are_soft_deleted() {
        $this
        ->withHeader('X-Company', self::$supplier->id)
        ->actingAs(self::$supplierAdmin)
        ->deleteJson(self::$endpoint)
        ->assertStatus(Response::HTTP_OK);

        // Manually dispatch the job immediately
        $job = new DeleteInventoryStock(self::$inventory);
        Bus::dispatchNow($job);

        $this->assertSoftDeleted('local_market_inventory_units', ['local_market_inventory_id' => self::$inventory->id]);
    }
}
