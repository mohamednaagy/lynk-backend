<?php

namespace Endpoints\Api\V1\Supplier\CommodityItem\Inventory;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\CommodityItem;
use App\Models\LocalMarketInventory;
use App\Models\User;
use App\Transformers\LocalMarketInventoryTransformer;
use App\Transformers\Supplier\CommodityItem\CommodityItemsTransformer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;
use Tests\Traits\InteractsWithCommodityItem;
use Tests\Traits\InteractsWithSupplier;

class InventoryControllerStoreTest extends TestCase
{
    use AssertsAccessByRoleAndArea, InteractsWithCommodityItem ,  InteractsWithSupplier, RefreshDatabase;

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

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();
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
            perm(Area::CommoditySupplier, [Subject::CommoditySupplierInventories, Action::Index])
        );

        self::$endpoint = 'api/v1/supplier/commodity-items/'.self::$commodityItems->id.'/inventory';

        self::$inventory = [
            'location_id' => self::$location->id,
            'total_units' => 200,
        ];

    }

    public function test_that_un_auth_user_cant_commodity_item_Invemtory(): void
    {
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->postJson(self::$endpoint, self::$inventory)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_that_auth_user_without_location_id_cant_create_commodity_item_inventory(): void
    {
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->postJson(self::$endpoint, Arr::except(self::$inventory, ['location_id']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('location_id');
    }

    public function test_supplier_user_create_item_inventory_but_total_units_less_than_one(): void
    {
        $inv = self::$inventory;
        $inv['total_units'] = 0;
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->postJson(self::$endpoint, $inv)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('total_units');

    }

    public function test_store_commodity_item_inventory_successfully(): void
    {
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->postJson(self::$endpoint, self::$inventory)
            ->assertOk()
            ->assertExactJson(
                fractal(LocalMarketInventory::orderBy('id', 'desc')->first(), new LocalMarketInventoryTransformer())
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


    // public function test_supplier_user_cant_create_commodity_item_inventory_with_duplicate_location(): void
    // {
    //     $this
    //         ->withHeader('X-Company', self::$supplier->id)
    //         ->actingAs(self::$supplierAdmin)
    //         ->postJson(self::$endpoint, self::$inventory)
    //         ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
    //         ->assertJsonValidationErrorFor('location_id');

    // }
}
