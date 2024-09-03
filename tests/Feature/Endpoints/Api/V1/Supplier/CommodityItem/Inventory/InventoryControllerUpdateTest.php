<?php

namespace Endpoints\Api\V1\Supplier\CommodityItem\Inventory;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Jobs\LocalMarket\UpdateInventoryStock;
use App\Models\User;
use App\Observers\LocalMarketInventoryObserver;
use App\Transformers\LocalMarketInventoryTransformer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;
use Tests\Traits\InteractsWithCommodityInventory;
use Tests\Traits\InteractsWithCommodityItem;
use Tests\Traits\InteractsWithSupplier;

class InventoryControllerUpdateTest extends TestCase
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
            perm(Area::CommoditySupplier, [Subject::CommoditySupplierInventories, Action::Edit])
        );

        self::$endpoint = 'api/v1/supplier/commodity-items/'.self::$commodityItems->id.'/inventory/'.self::$inventory->id;

        self::$inventory2 = [
            'location_id' => self::$location->id,
            'total_units' => 20,
        ];

    }

    public function test_un_auth_user_cant_update_commodity_inventory(): void
    {
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->putJson(self::$endpoint, self::$inventory2)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_supplier_user_cant_update_commodity_inventory_without_total_units(): void
    {
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->putJson(self::$endpoint, Arr::except(self::$inventory2, 'total_units'))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The total units field is required.',
                'errors' => [
                    'total_units' => [
                        'The total units field is required.',
                    ],
                ],
            ]);
    }

    public function test_supplier_user_cant_update_inventory_when_total_units_equal_zero(): void
    {
        $inventory = self::$inventory2;
        $inventory['total_units'] = 0;
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->putJson(self::$endpoint, $inventory)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('total_units');

    }

    public function test_supplier_user_update_inventory_with_failed_format_total_units(): void
    {
        $inventory = self::$inventory2;
        $inventory['total_units'] = '120.45';
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->putJson(self::$endpoint, $inventory)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('total_units');

    }

    public function test_supplier_user_update_inventory_with_active_reserved_units(): void
    {
        $inventory = self::$inventory;
        $inventory->reserved_items = 50;
        $inventory->save();

        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->putJson(self::$endpoint, self::$inventory2)
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertExactJson([
                'message' => 'Reserved Units is greater than new Total Units',
                'code' => 1031,
            ],
            );
    }

    public function test_supplier_user_can_update_commodity_inventory_successfully(): void
    {
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->putJson(self::$endpoint, self::$inventory2)
            ->assertOk()
            ->assertExactJson(
                fractal(self::$inventory->refresh(), new LocalMarketInventoryTransformer())
                    ->parseIncludes([
                        'id',
                        'company_id',
                        'comapny_name',
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
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_update_inventory_stock_job_is_fired()
    {
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->putJson(self::$endpoint, self::$inventory2)
            ->assertOk();

        Queue::assertPushed(UpdateInventoryStock::class);
    }

    public function test_quantity_after_update_equals_generated_units()
    {
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->putJson(self::$endpoint, self::$inventory2)
            ->assertOk();

        // Verify the job was pushed
        Queue::assertPushed(UpdateInventoryStock::class);

        // Manually dispatch the job immediately
        $job = new UpdateInventoryStock(self::$inventory, self::$inventory2['total_units']);
        Bus::dispatchNow($job);

        // Ensure the units were created

        $this->assertEquals(self::$inventory2['total_units'], self::$inventory->units()->count());

    }

    public function test_can_update_quantity_equals_reserved_units()
    {
        $inventory = self::$inventory;
        $inventory->reserved_items = 50;
        $inventory->save();

        $inventory2 = self::$inventory2;
        $inventory2['total_units'] = '50';

        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->putJson(self::$endpoint, $inventory2)
            ->assertOk();

        Queue::assertPushed(UpdateInventoryStock::class);

    }
}
