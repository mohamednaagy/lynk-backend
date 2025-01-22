<?php

namespace Endpoints\Api\V1\Admin\Commodity\CommoditySupplier\Locations;

use App\Models\Supplier;
use App\Models\SupplierLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithCommoditySupplier;
use Tests\Traits\InteractsWithSupplier;
use Tests\Traits\InteractsWithUser;

class CommodityLocationControllerUpdateTest extends TestCase
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

    public function test_that_unauthenticated_user_cannot_update_location(): void
    {
        $this->patchJson(self::$endpoint, [])
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_that_authorized_admin_can_update_location(): void
    {
        $updateData = [
            'unique_identifier' => 'updated_location',
            'name' => 'Updated Location',
            'description' => 'Updated description of the location',
        ];

        $this->actingAs(self::$userAdmin)
            ->putJson(self::$endpoint, $updateData)
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
                'unique_identifier' => 'updated_location',
                'name' => 'Updated Location',
            ]);
    }

    public function test_that_unauthorized_user_cannot_update_location(): void
    {
        $unauthorizedUser = $this->createUser();
        $this->actingAs($unauthorizedUser)
            ->patchJson(self::$endpoint, [])
            ->assertForbidden()
            ->assertJson([
                'message' => __('User does not have the right roles.'),
            ]);
    }

    public function test_that_missing_unique_identifier_field_returns_validation_error(): void
    {
        $invalidData = [
            'name' => 'Updated Location',
            'description' => 'Updated description of the location',
        ];

        $this->actingAs(self::$userAdmin)
            ->patchJson(self::$endpoint, $invalidData)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['unique_identifier']);
    }

    public function test_that_missing_name_field_returns_validation_error(): void
    {
        $invalidData = [
            'unique_identifier' => 'updated_location',
            'description' => 'Updated description of the location',
        ];

        $this->actingAs(self::$userAdmin)
            ->patchJson(self::$endpoint, $invalidData)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_that_duplicate_unique_identifier_returns_error(): void
    {
        SupplierLocation::factory()->create([
            'company_id' => self::$supplier->id,
            'unique_identifier' => 'taken_identifier',
        ]);

        $duplicateData = [
            'unique_identifier' => 'taken_identifier',
            'name' => 'Duplicate Location',
            'description' => 'Trying to use a duplicate unique identifier',
        ];

        $this->actingAs(self::$userAdmin)
            ->patchJson(self::$endpoint, $duplicateData)
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

    public function test_that_unique_identifier_with_spaces_returns_validation_error(): void
    {
        $invalidData = [
            'unique_identifier' => 'abc def',
            'name' => 'Updated Location',
            'description' => 'Updated description of the location',
        ];

        $this->actingAs(self::$userAdmin)
            ->patchJson(self::$endpoint, $invalidData)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['unique_identifier'])
            ->assertJsonFragment([
                'unique_identifier' => ['The unique identifier format is invalid.'],
            ]);
    }
}
