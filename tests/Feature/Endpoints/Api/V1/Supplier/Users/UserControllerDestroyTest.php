<?php

namespace Tests\Feature\Endpoints\Api\V1\Supplier\Users;

use App\Enums\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithCommoditySupplier;
use Tests\Traits\InteractsWithSupplier;
use Tests\Traits\InteractsWithUser;

class UserControllerDestroyTest extends TestCase
{
    use InteractsWithCommoditySupplier, InteractsWithSupplier, InteractsWithUser, RefreshDatabase;

    private static Supplier $supplier;

    private static User $supplierAdmin;

    private static User $supplierAdmin2;

    private static User $supplierUser;

    private static string $endpoint;

    protected function setUp(): void
    {
        parent::setUp();

        self::$supplier = $this->createSupplier();
        self::$supplierUser = $this->createSupplierUser(self::$supplier->id);
        self::$supplierAdmin = $this->createSupplierUser(
            self::$supplier->id,
            Role::SupplierAdmin,
            [
                'email_verified_at' => now(),
            ]
        );
        self::$supplierAdmin2 = $this->createSupplierUser(
            self::$supplier->id,
            Role::SupplierAdmin,
            [
                'email_verified_at' => now(),
            ]
        );
        self::$endpoint = 'api/v1/supplier/users/'.self::$supplierUser->id;
    }

    public function test_un_auth_user_cant_delete_supplier_user(): void
    {
        $this->deleteJson(self::$endpoint)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_supplier_admin_can_delete_supplier_user_successful(): void
    {
        $this->actingAs(self::$supplierAdmin)
            ->deleteJson(self::$endpoint)
            ->assertOk()
            ->assertExactJson(['data' => []]);
    }

    public function test_supplier_admin_cant_delete_himself(): void
    {
        $this->actingAs(self::$supplierUser)
            ->deleteJson(self::$endpoint)
            ->assertForbidden();
    }
}
