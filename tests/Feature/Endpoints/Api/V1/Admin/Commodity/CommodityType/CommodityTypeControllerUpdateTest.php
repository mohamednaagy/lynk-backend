<?php

namespace Endpoints\Api\V1\Admin\Commodity\CommodityType;

use App\Enums\Role;
use App\Models\CommodityType;
use App\Models\User;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;
use Tests\Traits\InteractsWithCommodityType;

class CommodityTypeControllerUpdateTest extends TestCase
{
    use AssertsAccessByRoleAndArea, InteractsWithCommodityType , RefreshDatabase;

    private static User $userAdmin;

    private static User $userManager;

    private static $commodityType;

    private static $commodityTypeDetails;

    private $endpoint = 'api/v1/admin/commodity-types';

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$userAdmin = $this->createSuperAdminUser();

        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        self::$commodityType = $this->createCommodityType();
        self::$commodityTypeDetails = [
            'name' => 'new type',
            'unique_name' => 'new unique name',
            'status' => '1',
            'description' => 'new description',
        ];

        $this->endpoint = 'api/v1/admin/commodity-types/'.self::$commodityType->id;
    }

    public function test_un_auth_user_cant_update_commodity_type(): void
    {
        $this->putJson($this->endpoint, self::$commodityTypeDetails)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_admin_can_update_commodity_type_successfully(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson($this->endpoint, self::$commodityTypeDetails)
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);

        $this->assertEquals(self::$commodityType->refresh()->unique_name, 'new unique name');
    }

    public function test_manager_without_permissions_cant_update_commodity_type(): void
    {
        Grantify::syncPermissionToModel(self::$userManager, []);

        $this->actingAs(self::$userManager)
            ->putJson($this->endpoint, self::$commodityTypeDetails)
            ->assertForbidden();
    }

    public function test_admin_cant_update_commodity_type_without_name(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson($this->endpoint, Arr::except(self::$commodityTypeDetails, 'name'))
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

    public function test_admin_cant_update_commodity_type_without_unique_name(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson($this->endpoint, Arr::except(self::$commodityTypeDetails, 'unique_name'))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The unique name field is required.',
                'errors' => [
                    'unique_name' => [
                        'The unique name field is required.',
                    ],
                ],
            ]);
    }

    public function test_admin_cant_update_commodity_type_with_exist_unique_name(): void
    {
        CommodityType::query()->create(self::$commodityTypeDetails);

        $this->actingAs(self::$userAdmin)
            ->putJson($this->endpoint, self::$commodityTypeDetails)
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The name has already been taken. (and 1 more error)',
                'errors' => [
                    'name' => [
                        'The name has already been taken.',
                    ],
                    'unique_name' => [
                        'The unique name has already been taken.',
                    ],

                ],
            ]);
    }
}
