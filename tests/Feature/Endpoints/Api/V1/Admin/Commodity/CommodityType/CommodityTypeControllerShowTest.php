<?php

namespace Endpoints\Api\V1\Admin\Commodity\CommodityType;

use App\Enums\Role;
use App\Models\User;
use App\Transformers\CommodityTypeTransformer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;
use Tests\Traits\InteractsWithCommodityType;

class CommodityTypeControllerShowTest extends TestCase
{
    use AssertsAccessByRoleAndArea, InteractsWithCommodityType , RefreshDatabase;

    private static User $userAdmin;

    private static User $userManager;

    private static $type;

    private $endpoint = 'api/v1/admin/commodity-types';

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$userAdmin = $this->createSuperAdminUser();

        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        self::$type = $this->createCommodityType();
        $this->endpoint = 'api/v1/admin/commodity-types/'.self::$type->id;
    }

    public function test_un_auth_user_cant_show_commodity_type(): void
    {
        $this->getJson($this->endpoint)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_admin_user_can_show_commodity_type_successfully(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson($this->endpoint)
            ->assertOk()
            ->assertJson(
                fractal(self::$type, new CommodityTypeTransformer())
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

    public function test_manager_without_permissions_cant_show_commodity_type(): void
    {
        Grantify::syncPermissionToModel(self::$userManager, []);

        $this->actingAs(self::$userManager)
            ->getJson($this->endpoint)
            ->assertForbidden();
    }
}
