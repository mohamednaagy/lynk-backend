<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Commodity\CommoditySupplier\Locations;

use App\Models\Supplier;
use App\Models\SupplierLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithCommoditySupplier;
use Tests\Traits\InteractsWithSupplier;
use Tests\Traits\InteractsWithUser;

class CommodityLocationControllerShowTest extends TestCase
{
    use InteractsWithCommoditySupplier, InteractsWithSupplier, InteractsWithUser, RefreshDatabase;

    private static Supplier $supplier;

    private static User $userAdmin;

    private static SupplierLocation $location;

    private static string $endpoint;

    public function setUp(): void
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

    public function test_that_unauthenticated_user_cannot_view_location(): void
    {
        $this->getJson(self::$endpoint)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_that_authorized_admin_can_view_location(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson(self::$endpoint)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'unique_identifier',
                    'name',
                    'description',
                ],
            ])
            ->assertJsonFragment([
                'id' => self::$location->id,
                'unique_identifier' => self::$location->unique_identifier,
                'name' => self::$location->name,
                'description' => self::$location->description,
            ]);
    }

    public function test_that_unauthorized_user_cannot_view_location(): void
    {
        $unauthorizedUser = $this->createUser();

        $this->actingAs($unauthorizedUser)
            ->getJson(self::$endpoint)
            ->assertForbidden()
            ->assertJson([
                'message' => __('User does not have the right roles.'),
            ]);
    }

    public function test_that_nonexistent_location_returns_not_found(): void
    {
        $nonExistentEndpoint = '/api/v1/admin/commodity-suppliers/'.self::$supplier->id.'/locations/99999';

        $this->actingAs(self::$userAdmin)
            ->getJson($nonExistentEndpoint)
            ->assertNotFound();
    }
}
