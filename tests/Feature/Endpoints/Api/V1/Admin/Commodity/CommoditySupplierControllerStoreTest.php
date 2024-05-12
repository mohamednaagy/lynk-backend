<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Commodity;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\CommoitySupplierMarketType;
use App\Enums\CommoitySupplierStatus;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\CommoditySupplier;
use App\Models\User;
use App\Transformers\CommoditySuppliersTransformer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithCommoditySupplier;
use Tests\Traits\InteractsWithUser;

class CommoditySupplierControllerStoreTest extends TestCase
{
    use InteractsWithCommoditySupplier, InteractsWithUser, RefreshDatabase;

    private static User $userAdmin;

    private static User $userManager;

    private static $suppliers;

    private $endpoint = 'api/v1/admin/commodity-suppliers';

    private static array $commoditySuppllier;

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$userAdmin = $this->createSuperAdminUser();
        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(
            self::$userManager,
            perm(Area::SuperAdmin, [Subject::Lenders, Action::Index])
        );

        self::$commoditySuppllier = [
            'legal_name' => 'new legal name'.rand(11, 999),
            'unique_name' => 'new unique name'.rand(11, 999),
            'description' => '1001280070',
            'status' => CommoitySupplierStatus::Active(),
            'market_type' => CommoitySupplierMarketType::Local(),
        ];

    }

    public function test_that_un_auth_user_cant_commodity_supplier(): void
    {
        $this->postJson($this->endpoint, self::$commoditySuppllier)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_that_auth_user_without_legal_name_cant_commodity_supplier(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson($this->endpoint, Arr::except(self::$commoditySuppllier, ['legal_name']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('legal_name');
    }

    public function test_that_auth_user_without_unique_name_cant_commodity_supplier(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson($this->endpoint, Arr::except(self::$commoditySuppllier, ['unique_name']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('unique_name');
    }

    public function test_store_commodity_supplier_with_unique_name(): void
    {
        $this->createCommoditySupplier(null, 123);
        $this->actingAs(self::$userAdmin)
            ->postJson($this->endpoint, array_merge(self::$commoditySuppllier, ['unique_name' => '123']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('unique_name');
    }

    public function test_store_commodity_supplier_with_invalid_status(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson($this->endpoint, array_merge(self::$commoditySuppllier, ['status' => '3']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('status');
    }

    public function test_store_commodity_supplier_with_invalid_market_type(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson($this->endpoint, array_merge(self::$commoditySuppllier, ['market_type' => '3']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('market_type');
    }

    public function test_store_commodity_supplier_success(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson($this->endpoint, self::$commoditySuppllier)
            ->assertOk()
            ->assertExactJson(
                fractal(CommoditySupplier::latest()->first(), new CommoditySuppliersTransformer())
                    ->parseIncludes([
                        'id',
                        'legal_name',
                        'description',
                        'unique_name',
                        'market_type',
                        'status',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }
}
