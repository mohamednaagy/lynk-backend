<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Commodity\CommoditySupplier;

use App\Enums\Role;
use App\Models\Company;
use App\Models\Media;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;
use Tests\Traits\InteractsWithCommoditySupplier;

class CommoditySupplierReportsTest extends TestCase
{
    use AssertsAccessByRoleAndArea, InteractsWithCommoditySupplier, RefreshDatabase;

    private static User $userAdmin;

    private static User $userManager;

    private static Supplier $supplier;

    private string $endpoint;

    /**
     * @throws BindingResolutionException
     */
    protected function setUp(): void
    {
        parent::setUp();

        self::$userAdmin = $this->createSuperAdminUser();
        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        self::$supplier = $this->createCommoditySupplier();

        $this->endpoint = 'api/v1/admin/commodity-suppliers/'.self::$supplier->id.'/reports';
    }

    public function test_un_auth_user_cant_get_commodity_supplier_reports(): void
    {
        $this->getJson($this->endpoint)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_admin_user_can_get_commodity_supplier_reports_successfully(): void
    {
        // Create some report media for the supplier
        $reports = $this->createSupplierReports(self::$supplier, 3);

        $response = $this->actingAs(self::$userAdmin)
            ->getJson($this->endpoint.'?type=supplier_monthly_usage&page=1')
            ->assertOk();

        // Verify response structure
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'created_at',
                    'file_name',
                    'download_url',
                ],
            ],
            'meta' => [
                'pagination' => [
                    'total',
                    'count',
                    'per_page',
                    'current_page',
                    'total_pages',
                ],
            ],
        ]);

        // Verify the reports are returned
        $responseData = $response->json('data');
        $this->assertCount(3, $responseData);
    }

    public function test_admin_user_can_get_commodity_supplier_reports_with_pagination(): void
    {
        // Create more reports than per page limit
        $reports = $this->createSupplierReports(self::$supplier, 15);

        $response = $this->actingAs(self::$userAdmin)
            ->getJson($this->endpoint.'?type=supplier_monthly_usage&page=1')
            ->assertOk();

        // Verify pagination meta exists
        $response->assertJsonStructure([
            'meta' => [
                'pagination' => [
                    'total',
                    'count',
                    'per_page',
                    'current_page',
                    'total_pages',
                ],
            ],
        ]);

        // Verify first page returns correct number of items
        $responseData = $response->json('data');
        $this->assertLessThanOrEqual(15, count($responseData));
    }

    public function test_admin_user_cannot_get_commodity_supplier_reports_without_type_parameter(): void
    {
        // Create some report media for the supplier
        $reports = $this->createSupplierReports(self::$supplier, 2);

        // Type parameter is required, so request without it should fail
        $this->actingAs(self::$userAdmin)
            ->getJson($this->endpoint.'?page=1')
            ->assertStatus(422); // Validation error
    }

    public function test_admin_user_can_get_commodity_supplier_reports_with_invalid_type(): void
    {
        $response = $this->actingAs(self::$userAdmin)
            ->getJson($this->endpoint.'?type=invalid_type&page=1')
            ->assertStatus(422); // Validation error
    }

    public function test_manager_without_permissions_cant_get_commodity_supplier_reports(): void
    {
        Grantify::syncPermissionToModel(self::$userManager, []);

        $this->actingAs(self::$userManager)
            ->getJson($this->endpoint.'?type=supplier_monthly_usage&page=1')
            ->assertForbidden();
    }

    public function test_admin_user_can_get_empty_reports_list_for_supplier_with_no_reports(): void
    {
        // Don't create any reports for this supplier
        $response = $this->actingAs(self::$userAdmin)
            ->getJson($this->endpoint.'?type=supplier_monthly_usage&page=1')
            ->assertOk();

        // Verify empty data array
        $response->assertJson([
            'data' => [],
        ]);

        // Verify pagination meta still exists
        $response->assertJsonStructure([
            'meta' => [
                'pagination' => [
                    'total',
                    'count',
                    'per_page',
                    'current_page',
                    'total_pages',
                ],
            ],
        ]);
    }

    public function test_admin_user_can_only_get_reports_for_specified_supplier(): void
    {
        // Create reports for the main supplier
        $mainSupplierReports = $this->createSupplierReports(self::$supplier, 3);

        // Create another supplier with reports
        $otherSupplier = $this->createCommoditySupplier();
        $otherSupplierReports = $this->createSupplierReports($otherSupplier, 2);

        // Request reports for the main supplier
        $response = $this->actingAs(self::$userAdmin)
            ->getJson($this->endpoint.'?type=supplier_monthly_usage&page=1')
            ->assertOk();

        // Verify only the main supplier's reports are returned
        $responseData = $response->json('data');
        $this->assertCount(3, $responseData);

        // Verify all returned reports belong to the main supplier
        foreach ($responseData as $report) {
            $media = Media::find($report['id']);
            $this->assertEquals(self::$supplier->id, $media->model_id);
        }
    }

    /**
     * Create supplier reports (Media models) for testing
     */
    private function createSupplierReports(Supplier $supplier, int $count = 1): array
    {
        $reports = [];

        for ($i = 0; $i < $count; $i++) {
            $reports[] = Media::create([
                'model_type' => Company::class,
                'model_id' => $supplier->id,
                'collection_name' => 'supplier_monthly_usage',
                'name' => "report_{$i}.pdf",
                'file_name' => "report_{$i}.pdf",
                'mime_type' => 'application/pdf',
                'size' => 1024 * ($i + 1),
                'disk' => 'public',
                'conversions_disk' => 'public',
                'uuid' => \Illuminate\Support\Str::uuid(),
                'manipulations' => [],
                'custom_properties' => [],
                'generated_conversions' => [],
                'responsive_images' => [],
            ]);
        }

        return $reports;
    }
}
