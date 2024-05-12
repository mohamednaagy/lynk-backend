<?php

namespace Endpoints\Api\V1\Admin\Commodity\CommodityType;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\CommodityTypeStatus;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\CommodityType;
use App\Models\User;
use App\Transformers\CommodityTypeTransformer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithCommodityType;
use Tests\Traits\InteractsWithUser;

class CommodityTypeControllerStoreTest extends TestCase
{
    use InteractsWithCommodityType, InteractsWithUser, RefreshDatabase;

    private static User $userAdmin;

    private static User $userManager;

    private static $types;

    private $endpoint = 'api/v1/admin/commodity-types';

    private static array $commodityType;

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

        self::$commodityType = [
            'name' => 'new legal name'.rand(11, 999),
            'unique_name' => 'new unique name'.rand(11, 999),
            'description' => '1001280070',
            'status' => CommodityTypeStatus::Active(),
        ];

    }

    public function test_that_un_auth_user_cant_commodity_type(): void
    {
        $this->postJson($this->endpoint, self::$commodityType)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_that_auth_user_without_name_cant_create_commodity_type(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson($this->endpoint, Arr::except(self::$commodityType, ['name']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('name');
    }

    public function test_that_auth_user_without_unique_name_cant_commodity_type(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson($this->endpoint, Arr::except(self::$commodityType, ['unique_name']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('unique_name');
    }

    public function test_store_commodity_type_with_unique_name(): void
    {
        $this->createCommodityType(null, 123);
        $this->actingAs(self::$userAdmin)
            ->postJson($this->endpoint, array_merge(self::$commodityType, ['unique_name' => '123']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('unique_name');
    }

    public function test_store_commodity_type_with_invalid_status(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson($this->endpoint, array_merge(self::$commodityType, ['status' => '3']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('status');
    }

    public function test_store_commodity_type_success(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson($this->endpoint, self::$commodityType)
            ->assertOk()
            ->assertExactJson(
                fractal(CommodityType::latest()->first(), new CommodityTypeTransformer())
                    ->parseIncludes([
                        'id',
                        'name',
                        'description',
                        'unique_name',
                        'status',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }
}
