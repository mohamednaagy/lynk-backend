<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Commodity\CommoditySupplier\Users;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithCommoditySupplier;
use Tests\Traits\InteractsWithSupplier;
use Tests\Traits\InteractsWithUser;

class SupplierUserControllerDeleteTest extends TestCase
{
    use InteractsWithCommoditySupplier, InteractsWithSupplier, InteractsWithUser, RefreshDatabase;

    private static Supplier $supplier;

    private static User $userAdmin;

    private static User $supplierUser;

    private static string $endpoint;

    protected function setUp(): void
    {
        parent::setUp();

        self::$supplier = $this->createSupplier();
        self::$userAdmin = $this->createSuperAdminUser();
        self::$supplierUser = $this->createSupplierUser(self::$supplier->id);

        self::$endpoint = 'api/v1/admin/commodity-suppliers/'.self::$supplier->id.'/users/';
    }

    public function test_un_auth_user_cant_delete_supplier_user(): void
    {
        $this->deleteJson(self::$endpoint.(int) self::$supplierUser->id)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_admin_user_can_delete_supplier_user_successful(): void
    {
        $this->actingAs(self::$userAdmin)
            ->deleteJson(self::$endpoint.(int) self::$supplierUser->id)
            ->assertOk()
            ->assertExactJson(['data' => []]);
    }
}
