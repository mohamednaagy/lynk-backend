<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Commodity\CommoditySupplier\Locations;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;
use Tests\Traits\InteractsWithCommoditySupplier;
use Tests\Traits\InteractsWithSupplier;
use Tests\Traits\InteractsWithUser;

class CommodityLocationControllerIndexTest extends TestCase
{
    use InteractsWithCommoditySupplier, InteractsWithSupplier, InteractsWithUser, RefreshDatabase;

    private static Supplier $supplier;

    private static User $userAdmin;

    private static LengthAwarePaginator $locations;

    private static string $endpoint;

    public function setUp(): void
    {
        parent::setUp();

        // Create a supplier and admin user
        self::$supplier = $this->createSupplier();
        self::$userAdmin = $this->createSuperAdminUser();

        // Set endpoint for the API
        self::$endpoint = '/api/v1/admin/commodity-suppliers/'.self::$supplier->id.'/locations';

        // Prepare paginated locations data for response simulation
        self::$supplier->locations()->create([
            'unique_identifier' => 'abc',
            'name' => 'location name',
            'description' => 'location description',
        ]);

        self::$locations = self::$supplier->locations()->paginate();
    }

    public function test_that_unauthenticated_user_cannot_access_index(): void
    {
        $this->getJson(self::$endpoint)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_that_authorized_admin_user_can_access_index(): void
    {
        // Acting as admin with required permissions
        $this->actingAs(self::$userAdmin)
            ->getJson(self::$endpoint)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'unique_identifier',
                        'description',
                        'created_at',
                        'is_deletable',
                    ],
                ],
            ]);
    }

    public function test_index_api_returns_paginated_locations(): void
    {
        // Acting as super admin to access the locations index
        $this->actingAs(self::$userAdmin)
            ->getJson(self::$endpoint)
            ->assertOk()
            ->assertJsonFragment([
                'current_page' => 1,
                'per_page' => 15,  // Check if pagination params are correct (default values)
            ])
            ->assertJsonCount(self::$locations->count(), 'data');
    }

    public function test_that_unauthorized_user_cannot_access_index_with_permissions(): void
    {
        // Try accessing the endpoint without the necessary permissions
        $unauthorizedUser = $this->createUser();
        $this->actingAs($unauthorizedUser)
            ->getJson(self::$endpoint)
            ->assertForbidden()
            ->assertJson([
                'message' => __('User does not have the right roles.'),
            ]);
    }
}
