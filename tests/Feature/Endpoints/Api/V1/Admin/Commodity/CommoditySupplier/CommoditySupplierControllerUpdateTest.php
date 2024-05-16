<?php

namespace Endpoints\Api\V1\Admin\Commodity\CommoditySupplier;

use App\Enums\Role;
use App\Models\CommoditySupplier;
use App\Models\User;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;
use Tests\Traits\InteractsWithCommoditySupplier;

class CommoditySupplierControllerUpdateTest extends TestCase
{
    use AssertsAccessByRoleAndArea, InteractsWithCommoditySupplier , RefreshDatabase;

    private static User $userAdmin;

    private static User $userManager;

    private static $supplier;

    private static $supplierDetails;

    private $endpoint = 'api/v1/admin/commodity-suppliers';

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$userAdmin = $this->createSuperAdminUser();

        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        self::$supplier = $this->createCommoditySupplier();
        self::$supplierDetails = [
            'legal_name' => 'new legal supplier',
            'unique_name' => 'new unique name',
            'status' => '1',
            'market_type' => '1',
            'description' => 'new description',
        ];

        $this->endpoint = 'api/v1/admin/commodity-suppliers/'.self::$supplier->id;
    }

    public function test_un_auth_user_cant_update_commodity_supplier(): void
    {
        $this->putJson($this->endpoint, self::$supplierDetails)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_admin_can_update_commodity_supplier_successfully(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson($this->endpoint, self::$supplierDetails)
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);
        $this->assertEquals(self::$supplier->company->refresh()->name, 'new legal supplier');
        $this->assertEquals(self::$supplier->company->refresh()->unique_name, 'new unique name');
        $this->assertEquals(self::$supplier->refresh()->unique_name, 'new unique name');
    }

    public function test_manager_without_permissions_cant_update_commodity_supplier(): void
    {
        Grantify::syncPermissionToModel(self::$userManager, []);

        $this->actingAs(self::$userManager)
            ->putJson($this->endpoint, self::$supplierDetails)
            ->assertForbidden();
    }

    public function test_admin_cant_update_commodity_supplier_without_legal_name(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson($this->endpoint, Arr::except(self::$supplierDetails, 'legal_name'))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The legal name field is required.',
                'errors' => [
                    'legal_name' => [
                        'The legal name field is required.',
                    ],
                ],
            ]);
    }

    public function test_admin_cant_update_commodity_supplier_without_unique_name(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson($this->endpoint, Arr::except(self::$supplierDetails, 'unique_name'))
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

    public function test_admin_cant_update_commodity_supplier_with_exist_unique_name(): void
    {
        //        CommoditySupplier::query()->create(self::$supplierDetails);
        self::$supplier = $this->createCommoditySupplier(self::$supplierDetails['legal_name'], self::$supplierDetails['unique_name']);

        $this->actingAs(self::$userAdmin)
            ->putJson($this->endpoint, self::$supplierDetails)
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The legal name has already been taken. (and 1 more error)',
                'errors' => [
                    'legal_name' => [
                        'The legal name has already been taken.',
                    ],
                    'unique_name' => [
                        'The unique name has already been taken.',
                    ],

                ],
            ]);
    }
}
