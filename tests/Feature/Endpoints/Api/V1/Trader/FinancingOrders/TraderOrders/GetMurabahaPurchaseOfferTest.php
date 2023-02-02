<?php

namespace Tests\Feature\Endpoints\Api\V1\Trader\FinancingOrders\TraderOrders;

use App\Enums\Area;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\Media;
use App\Models\TraderOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class GetMurabahaPurchaseOfferTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    private static Company $company;

    private static User $traderAdminUser;

    private static Builder|Model $order;

    private static Builder|Model $traderOrder;

    private static string $apiUrl;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createTraderCompany('2000', ['company_cr' => '12345678911']);
        self::$traderAdminUser = $this->createTraderUser(self::$company->id);
        self::$order = $this->createOrder(self::$company->id, self::$traderAdminUser->id, [
            'status' => FinancingOrderStatus::ClientWakalaCompleted,
        ]);

        self::$traderOrder = self::$order->traderOrders()->create([
            'provider' => 'fake',
            'status' => TraderOrderStatus::InProgress,
            'reference' => 123,
        ]);

        Media::query()->create([
            'model_type' => TraderOrder::class,
            'model_id' => self::$traderOrder->id,
            'uuid' => Str::uuid(),
            'collection_name' => TraderOrderMediaCollection::MurabahaPurchaseOrder,
            'name' => 'media-libraryHdFscO',
            'file_name' => 'dmcc-6404.pdf',
            'mime_type' => 'application/pdf',
            'disk' => 'local',
            'conversions_disk' => 'local',
            'size' => '3028',
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
            'order_column' => '1',
        ]);

        self::$apiUrl = 'api/v1/trader/orders/'.self::$order->id.'/trader-orders/'.self::$traderOrder->id.'/murabaha-purchase-offer';
    }

    /**
     * @return void
     */
    public function test_unauth_user_cannot_access(): void
    {
        $this->withHeader('X-Company', self::$company->id)
            ->getJson(self::$apiUrl)
            ->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function test_auth_user_can_get_murabaha_purchase_offer(): void
    {
        $this->actingAs(self::$traderAdminUser)
            ->withHeader('X-Company', self::$company->id)
            ->getJson(self::$apiUrl)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'murabaha_purchase_offer',
                ],
            ]);
    }

    public function test_auth_user_can_update_process_murabaha_purchase_offer(): void
    {
        $this->actingAs(self::$traderAdminUser)
            ->withHeader('X-Company', self::$company->id)
            ->postJson(self::$apiUrl, [
                'document' => UploadedFile::fake()->create('test.pdf'),
            ])
            ->assertOk()
            ->assertJsonStructure([
                'data' => [],
            ]);
    }

    public function test_other_users_areas_can_not_update_process_murabaha_purchase_offer()
    {
        $this->assertStatusCodeForAllRolesExceptForArea(Response::HTTP_FORBIDDEN, [Area::Trader], function ($user, $role) {
            return $this->actingAs($user)
                ->withHeader('X-Company', self::$company->id)
                ->getJson(self::$apiUrl);
        });
    }
}
