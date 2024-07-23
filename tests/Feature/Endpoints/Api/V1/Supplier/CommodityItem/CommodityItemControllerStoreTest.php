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
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;
use Tests\Traits\InteractsWithCommodityItem;
use Tests\Traits\InteractsWithSupplier;

class CommodityItemControllerStoreTest extends TestCase
{
    use AssertsAccessByRoleAndArea, InteractsWithCommodityItem ,  InteractsWithSupplier, RefreshDatabase;

    private static User $supplierAdmin;

    private static User $supplierAdmin2;

    private static User $userManager;

    private static $commodityItems;

    private static $supplier;

    private static $supplier2;

    private $endpoint = 'api/v1/supplier/commodity-items';

    private static array $commodityItem;

    private static array $commodityItem2;

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
        self::$supplierAdmin2 = $this->createSupplierUser(
            self::$supplier2->id,
            Role::SupplierAdmin,
            [
                'email_verified_at' => now(),
            ]
        );
        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        self::$commodityItems = $this->getCommodityItems(self::$supplier, 5, true);
        $this->assignPermissionToUser(
            self::$userManager,
            perm(Area::CommoditySupplier, [Subject::CommoditySupplierItems, Action::Index])
        );

        self::$commodityItem = [
            'name' => 'name'.rand(11, 999),
            'unique_name' => 'unique name'.rand(11, 999),
            'description' => 'Test Description',
            'company_id' => self::$supplier->id,
            'min_price' => 10,
            'max_price' => 20,
            'volume_sellable_unit' => 10,
            'currency_id' => $this->createCurrency()->id,
            'measurement_id' => $this->createMeasurement()->id,
            'commodity_type_id' => $this->createCommodityType('type', 'test_item')->id,
        ];

        self::$commodityItem2 = [
            'name' => 'name'.rand(11, 999),
            'unique_name' => 'unique name'.rand(11, 999),
            'description' => 'Test Description',
            'company_id' => self::$supplier->id,
            'min_price' => 10,
            'max_price' => 20,
            'volume_sellable_unit' => 10,
            'currency_id' => $this->createCurrency()->id,
            'measurement_id' => $this->createMeasurement()->id,
            'commodity_type_id' => $this->createCommodityType()->id,
        ];
    }

    public function test_that_un_auth_user_cant_commodity_item(): void
    {
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->postJson($this->endpoint, self::$commodityItem)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_that_auth_user_without_name_cant_create_commodity_item(): void
    {
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->postJson($this->endpoint, Arr::except(self::$commodityItem, ['name']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('name');
    }

    public function test_supplier_user_create_item_but_max_price_less_than_min_price(): void
    {
        $item = self::$commodityItem;
        $item['min_price'] = 50;
        $item['max_price'] = 10;
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->postJson($this->endpoint, $item)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('max_price')
            ->assertJsonValidationErrorFor('min_price');

    }

    public function test_supplier_user_create_item_with_failed_format_volume_sellable_unit(): void
    {
        $item = self::$commodityItem;
        $item['volume_sellable_unit'] = '1234569,5858585';
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->postJson($this->endpoint, $item)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('volume_sellable_unit');

    }

    public function test_supplier_user_cant_create_commodity_item_without_unique_name(): void
    {
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->postJson($this->endpoint, Arr::except(self::$commodityItem, ['unique_name']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('unique_name');
    }

    public function test_supplier_user_cant_create_commodity_item_with_duplicate_unique_name(): void
    {
        $item = $this->createCommodityItem(self::$supplier, 'test', 'test_duplicate_unique_name');
        self::$commodityItem['unique_name'] = $item->unique_name;
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->postJson($this->endpoint, self::$commodityItem)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('unique_name');

    }

    public function test_supplier_user_can_create_commodity_item_with_unique_name_exist_to_another_supplier(): void
    {
        $item = $this->createCommodityItem(self::$supplier);
        self::$commodityItem2['unique_name'] = $item->unique_name;

        $this
            ->withHeader('X-Company', self::$supplier2->id)
            ->actingAs(self::$supplierAdmin2)
            ->postJson($this->endpoint, self::$commodityItem2)
            ->assertOk()
            ->assertExactJson(
                fractal(CommodityItem::orderBy('id', 'desc')->first(), new CommodityItemsTransformer())
                    ->parseIncludes([
                        'id',
                        'name',
                        'unique_name',
                        'commodity_type',
                        'created_at',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_store_commodity_item_with_unique_name(): void
    {
        $this->createCommodityItem(self::$supplier, unique_name: 'test');
        self::$commodityItem['unique_name'] = 'test';
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->postJson($this->endpoint, self::$commodityItem)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('unique_name');
    }

    public function test_store_commodity_item_success(): void
    {
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->postJson($this->endpoint, self::$commodityItem)
            ->assertOk()
            ->assertExactJson(
                fractal(CommodityItem::orderBy('id', 'desc')->first(), new CommodityItemsTransformer())
                    ->parseIncludes([
                        'id',
                        'name',
                        'unique_name',
                        'commodity_type',
                        'created_at',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }
}
