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

class CommodityItemControllerUpdateTest extends TestCase
{
    use AssertsAccessByRoleAndArea, InteractsWithCommodityItem ,  InteractsWithSupplier, RefreshDatabase;

    private static User $supplierAdmin;

    private static User $supplierAdmin2;

    private static User $userManager;

    private static $commodityItems;

    private static $supplier;

    private static $supplier2;

    private static CommodityItem $item;

    private static CommodityItem $item2;

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
        self::$item = $this->createCommodityItem(self::$supplier, 'test_update', 'test_update_unique');
        self::$item2 = $this->createCommodityItem(self::$supplier2, 'test_update2', 'test_update_unique2');

        $this->endpoint = $this->endpoint.'/'.self::$item->id;

    }

    public function test_un_auth_user_cant_update_commodity_item(): void
    {
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->putJson($this->endpoint, self::$commodityItem)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_supplier_user_cant_update_commodity_item_without_name(): void
    {
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->putJson($this->endpoint, Arr::except(self::$commodityItem, 'name'))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The name field is required.',
                'errors' => [
                    'name' => [
                        'The name field is required.',
                    ],
                ],
            ]);
    }

    public function test_supplier_user_update_item_but_max_price_less_than_min_price(): void
    {
        $item = self::$commodityItem;
        $item['min_price'] = 50;
        $item['max_price'] = 10;
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->putJson($this->endpoint, $item)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('max_price')
            ->assertJsonValidationErrorFor('min_price');

    }

    public function test_supplier_user_update_item_with_failed_format_volume_sellable_unit(): void
    {
        $item = self::$commodityItem;
        $item['volume_sellable_unit'] = '1234569,5858585';
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->putJson($this->endpoint, $item)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('volume_sellable_unit');

    }

    public function test_supplier_user_update_item_with_related_to_another_supplier(): void
    {
        $endpoint2 = 'api/v1/supplier/commodity-items/'.self::$item2->id;

        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->putJson($endpoint2, self::$commodityItem2)
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function test_supplier_user_cant_update_commodity_item_without_unique_name(): void
    {
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->putJson($this->endpoint, Arr::except(self::$commodityItem, ['unique_name']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('unique_name');
    }

    public function test_supplier_user_cant_update_commodity_item_with_invalid_format_volume(): void
    {
        self::$commodityItem['volume_sellable_unit'] = '12345678,987654';
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->putJson($this->endpoint, self::$commodityItem)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('volume_sellable_unit');
    }

    public function test_supplier_user_can_update_commodity_item_with_valid_format_volume(): void
    {
        self::$commodityItem['volume_sellable_unit'] = '12345678,12345';
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->putJson($this->endpoint, self::$commodityItem)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('volume_sellable_unit');
    }

    public function test_supplier_user_can_update_commodity_item_with_successfully(): void
    {
        $this
            ->withHeader('X-Company', self::$supplier->id)
            ->actingAs(self::$supplierAdmin)
            ->putJson($this->endpoint, self::$commodityItem)
            ->assertOk()
            ->assertExactJson(
                fractal(self::$item->refresh(), new CommodityItemsTransformer())
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
