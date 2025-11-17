<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Commodity\CommodityItem;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\LocalMarket\InventoryStatus as LocalMarketInventoryStatus;
use App\Enums\Role;
use App\Enums\Subject;
use App\Jobs\LocalMarket\DeleteCommodityItem;
use App\Jobs\LocalMarket\UpdateInventoryStock;
use App\Models\CommodityItem;
use App\Models\LocalMarketInventory;
use App\Models\SupplierLocation;
use App\Models\User;
use App\Observers\LocalMarketInventoryObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;
use Tests\Traits\InteractsWithCommodityInventory;
use Tests\Traits\InteractsWithCommodityItem;
use Tests\Traits\InteractsWithSupplier;

class CommodityItemControllerDestroyTest extends TestCase
{
    use AssertsAccessByRoleAndArea, InteractsWithCommodityInventory, InteractsWithCommodityItem, InteractsWithSupplier, RefreshDatabase;

    private static User $userAdmin;

    private static User $userManager;

    private static CommodityItem $item;

    private static string $endpoint;

    private static array $commodityItem;

    private static $inventory;

    private static $inventoryData;

    private static $supplier;

    private static $location;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a admin, admin user, supplier, and a commodity item
        self::$userAdmin = $this->createSuperAdminUser();
        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        self::$userAdmin = $this->createSuperAdminUser();
        self::$supplier = $this->createSupplier();

        $this->assignPermissionToUser(
            self::$userManager,
            perm(Area::SuperAdmin, [Subject::CommodityMarketCommodityItems, Action::Delete])
        );

        self::$commodityItem = [
            'name' => 'name'.rand(11, 999),
            'unique_name' => 'unique name'.rand(11, 999),
            'description' => 'Test Description',
            'commodity_supplier_id' => self::$supplier->id,
            'min_price' => 10,
            'max_price' => 20,
            'volume_sellable_unit' => 10,
            'currency_id' => $this->createCurrency()->id,
            'measurement_id' => $this->createMeasurement()->id,
        ];

        self::$location = $this->createSupplierLocation(
            self::$supplier,
            'name'.rand(11, 999),
            'unique name'.rand(11, 999),
            'Test Description',
        );

        self::$item = $this->createCommodityItem(self::$supplier, 'test_update', 'test_update_unique');

        self::$inventoryData = [
            'company_id' => self::$supplier->id,
            'commodity_item_id' => self::$item->id,
            'commodity_type_id' => self::$item->commodity_type_id,
            'supplier_location_id' => self::$location->id,
            'min_price' => self::$item->min_price,
            'max_price' => self::$item->max_price,
            'reserved_items' => 0,
            'available_quantity' => 300,
            'status' => LocalMarketInventoryStatus::Pending,
        ];

        self::$inventory = LocalMarketInventory::create(self::$inventoryData);

        $observer = new LocalMarketInventoryObserver;
        $observer->created(self::$inventory);

        $job = new UpdateInventoryStock(self::$inventory, 300, true);
        Bus::dispatchSync($job);

        // Set the endpoint for the API
        self::$endpoint = '/api/v1/admin/commodity-items/'.self::$item->id;
    }

    public function test_that_unauthenticated_user_cannot_delete_item(): void
    {
        $this->deleteJson(self::$endpoint)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_that_authorized_admin_can_delete_item(): void
    {
        // Fake the dispatch of the delete job
        Queue::fake();

        $this->actingAs(self::$userAdmin)
            ->deleteJson(self::$endpoint)
            ->assertOk()
            ->assertJson([
                'data' => [],
            ]);

        // Assert that the delete job was dispatched
        Queue::assertPushed(DeleteCommodityItem::class, function ($job) {
            $reflection = new \ReflectionClass($job);
            $property = $reflection->getProperty('commodityItem');
            $property->setAccessible(true); // Make the protected property accessible

            // Return true if the job's supplierLocation is the same as the location
            return $property->getValue($job)->is(self::$item);
        });
    }

    public function test_that_admin_cannot_delete_non_deletable_item(): void
    {
        // Make the location non-deletable
        self::$inventory->reserved_items = 1;
        self::$inventory->save();

        $this->actingAs(self::$userAdmin)
            ->deleteJson(self::$endpoint)
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertExactJson([
                'message' => 'Reserved Units is greater than 0. Commodity Item cannot be deleted.',
                'code' => 1042,
            ]);

    }

    public function test_that_unauthorized_user_cannot_delete_item(): void
    {
        $unauthorizedUser = $this->createUser();
        $this->actingAs($unauthorizedUser)
            ->deleteJson(self::$endpoint)
            ->assertForbidden()
            ->assertJson([
                'message' => __('User does not have the right roles.'),
            ]);
    }
}
