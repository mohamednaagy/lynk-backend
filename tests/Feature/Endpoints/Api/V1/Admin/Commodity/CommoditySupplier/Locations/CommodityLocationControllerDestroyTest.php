<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Commodity\CommoditySupplier\Locations;

use App\Enums\LocalMarket\InventoryStatus as LocalMarketInventoryStatus;
use App\Jobs\LocalMarket\DeleteSupplierLocation as DeleteSupplierLocationJob;
use App\Models\LocalMarketInventory;
use App\Models\Supplier;
use App\Models\SupplierLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\InteractsWithCommodityInventory;
use Tests\Traits\InteractsWithSupplier;
use Tests\Traits\InteractsWithUser;

class CommodityLocationControllerDestroyTest extends TestCase
{
    use InteractsWithCommodityInventory, InteractsWithSupplier, InteractsWithUser, RefreshDatabase;

    private static Supplier $supplier;

    private static User $userAdmin;

    private static SupplierLocation $location;

    private static string $endpoint;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a supplier, admin user, and a location
        self::$supplier = $this->createSupplier();
        self::$userAdmin = $this->createSuperAdminUser();
        self::$location = SupplierLocation::factory()->create([
            'company_id' => self::$supplier->id,
        ]);

        // Set the endpoint for the API
        self::$endpoint = '/api/v1/admin/commodity-suppliers/'.self::$supplier->id.'/locations/'.self::$location->id;
    }

    public function test_that_unauthenticated_user_cannot_delete_location(): void
    {
        $this->deleteJson(self::$endpoint)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_that_authorized_admin_can_delete_location(): void
    {
        // Fake the dispatch of the delete job
        Queue::fake();

        $this->actingAs(self::$userAdmin)
            ->deleteJson(self::$endpoint)
            ->assertOk()
            ->assertJson([
                'data' => [],
            ]);

        // Assert that the delete job was dispatched
        Queue::assertPushed(DeleteSupplierLocationJob::class, function ($job) {
            $reflection = new \ReflectionClass($job);
            $property = $reflection->getProperty('supplierLocation');
            $property->setAccessible(true); // Make the protected property accessible

            // Return true if the job's supplierLocation is the same as the location
            return $property->getValue($job)->is(self::$location);
        });
    }

    public function test_that_admin_cannot_delete_non_deletable_location(): void
    {
        // Make the location non-deletable
        LocalMarketInventory::factory([
            'reserved_items' => 1,
            'supplier_location_id' => self::$location->id,
            'status' => LocalMarketInventoryStatus::Active,
        ])->create();

        $this->actingAs(self::$userAdmin)
            ->deleteJson(self::$endpoint)
            ->assertBadRequest()
            ->assertJson([
                'message' => __('error.location_cannot_be_deleted'),
            ]);
    }

    public function test_that_unauthorized_user_cannot_delete_location(): void
    {
        $unauthorizedUser = $this->createUser();
        $this->actingAs($unauthorizedUser)
            ->deleteJson(self::$endpoint)
            ->assertForbidden()
            ->assertJson([
                'message' => __('User does not have the right roles.'),
            ]);
    }
}
