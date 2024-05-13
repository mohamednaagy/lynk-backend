<?php

namespace Endpoints\Api\V1\Admin\Commodity\CommodityType;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\User;
use App\Transformers\CommodityTypeTransformer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;
use Tests\Traits\InteractsWithCommodityType;

class CommodityTypeControllerIndexTest extends TestCase
{
    use AssertsAccessByRoleAndArea, InteractsWithCommodityType , RefreshDatabase;

    private static User $userAdmin;

    private static User $userManager;

    private static $commodityTypes;

    private $endpoint = 'api/v1/admin/commodity-types';

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$userAdmin = $this->createSuperAdminUser();
        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        self::$commodityTypes = $this->getCommodityType(5, true);

        $this->assignPermissionToUser(
            self::$userManager,
            perm(Area::SuperAdmin, [Subject::Lenders, Action::Index])
        );
    }

    public function test_un_auth_user_cant_index_commodity_suppliers(): void
    {
        $this->getJson($this->endpoint)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_admin_can_index_commodity_suppliers_successfully(): void
    {

        $this->actingAs(self::$userAdmin)
            ->getJson($this->endpoint)
            ->assertOk()
            ->assertExactJson(
                fractal(self::$commodityTypes, new CommodityTypeTransformer())
                    ->parseIncludes([
                        'id',
                        'name',
                        'unique_name',
                        'status',
                        'description',
                        'created_at',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_manager_without_permissions_cant_index_lenders(): void
    {
        $this->actingAs(self::$userManager)
            ->getJson($this->endpoint)
            ->assertForbidden();
    }
}
