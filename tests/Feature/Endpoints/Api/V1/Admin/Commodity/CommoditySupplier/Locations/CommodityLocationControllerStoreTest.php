<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Commodity\CommoditySupplier\Locations;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithCommoditySupplier;
use Tests\Traits\InteractsWithSupplier;
use Tests\Traits\InteractsWithUser;

class CommodityLocationControllerStoreTest extends TestCase
{
    use InteractsWithCommoditySupplier, InteractsWithSupplier, InteractsWithUser, RefreshDatabase;

    private static Supplier $supplier;

    private static User $userAdmin;

    private static string $endpoint;

    public function setUp(): void
    {
        parent::setUp();

        // Create a supplier and admin user
        self::$supplier = $this->createSupplier();
        self::$userAdmin = $this->createSuperAdminUser();

        // Set the endpoint for the API
        self::$endpoint = '/api/v1/admin/commodity-suppliers/'.self::$supplier->id.'/locations';
    }

    // Test: Reject unique_identifier with spaces
    public function test_that_unique_identifier_with_spaces_returns_validation_error(): void
    {
        // Prepare data with spaces in unique_identifier
        $invalidData = [
            'unique_identifier' => 'abc def',
            'name' => 'New Location',
            'description' => 'Description of the new location',
        ];

        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, $invalidData)
            ->assertUnprocessable() // Expecting validation error
            ->assertJsonValidationErrors(['unique_identifier'])
            ->assertJsonFragment([
                'unique_identifier' => ['The unique identifier format is invalid.'],
            ]);
    }

    public function test_that_unauthenticated_user_cannot_store_location(): void
    {
        $this->postJson(self::$endpoint, [])
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_that_authorized_admin_can_store_location(): void
    {
        $locationData = [
            'unique_identifier' => 'new_location_123',
            'name' => 'New Location',
            'description' => 'Description of the new location',
        ];

        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, $locationData)
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
                'unique_identifier' => 'new_location_123',
                'name' => 'New Location',
            ]);
    }

    public function test_that_unauthorized_user_cannot_store_location_with_permissions(): void
    {
        $unauthorizedUser = $this->createUser();
        $this->actingAs($unauthorizedUser)
            ->postJson(self::$endpoint, [])
            ->assertForbidden()
            ->assertJson([
                'message' => __('User does not have the right roles.'),
            ]);
    }

    public function test_that_missing_unique_identifier_field_returns_validation_error(): void
    {
        $invalidData = [
            'name' => 'New Location',
            'description' => 'Description of the new location',
        ];

        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, $invalidData)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['unique_identifier']);
    }

    public function test_that_missing_name_field_returns_validation_error(): void
    {
        $invalidData = [
            'unique_identifier' => 'location_123',
            'description' => 'Description of the new location',
        ];

        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, $invalidData)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_that_duplicate_unique_identifier_returns_error(): void
    {
        $existingLocationData = [
            'unique_identifier' => 'location_123',
            'name' => 'Existing Location',
            'description' => 'This is an existing location',
        ];

        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, $existingLocationData)
            ->assertOk();

        $duplicateLocationData = [
            'unique_identifier' => 'location_123',
            'name' => 'Duplicate Location',
            'description' => 'This location has a duplicate unique identifier',
        ];

        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, $duplicateLocationData)
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'This value already exists',
                'errors' => [
                    'unique_identifier' => [
                        'This value already exists',
                    ],
                ],
            ]);
    }
}
