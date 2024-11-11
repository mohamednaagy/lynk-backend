<?php

namespace Endpoints\Api\V1\Admin\CommodityItem;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\User;
use App\Transformers\Admin\CommodityItem\CommodityItemsTransformer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;
use Tests\Traits\InteractsWithCommodityItem;
use Tests\Traits\InteractsWithSupplier;

class CommodityItemControllerIndexTest extends TestCase
{
    use AssertsAccessByRoleAndArea, InteractsWithCommodityItem ,  InteractsWithSupplier, RefreshDatabase;

    private static User $userAdmin;

    private static User $userManager;

    private static $commodityItems;


    private $endpoint = 'api/v1/admin/commodity-items';

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();
        self::$userAdmin = $this->createSuperAdminUser();
        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        self::$commodityItems = $this->getAdminCommodityItems( 5, true);

        $this->assignPermissionToUser(
            self::$userManager,
            perm(Area::CommoditySupplier, [Subject::CommoditySupplierItems, Action::Index])
        );

    }

    public function test_un_auth_user_cant_index_commodity_items(): void
    {
        $this->getJson($this->endpoint)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_admin_can_index_commodity_items_successfully(): void
    {

        $this->actingAs(self::$userAdmin)
        ->getJson($this->endpoint)
        ->assertOk()
        ->assertExactJson(
                fractal(self::$commodityItems, new CommodityItemsTransformer())
                    ->parseIncludes([
                        'id',
                        'name',
                        'supplier',
                        'unique_name',
                        'company_id',
                        'commodity_type',
                        'available_units',
                        'reserved_units',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_manager_without_permissions_cant_index_items(): void
    {
        $this->actingAs(self::$userManager)
            ->getJson($this->endpoint)
            ->assertForbidden();
    }
}
