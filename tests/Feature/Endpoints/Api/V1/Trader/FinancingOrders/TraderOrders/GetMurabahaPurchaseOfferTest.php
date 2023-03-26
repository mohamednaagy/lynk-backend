<?php

namespace Tests\Feature\Endpoints\Api\V1\Trader\FinancingOrders\TraderOrders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\MurabhaStep;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\Media;
use App\Models\TraderOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\Support\FinancingOrders\TraderOrderScenario;
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

        self::$order = OrderScenario::inProgress()
            ->lender(self::$company)
            ->creator(self::$traderAdminUser)
            ->commit()
            ->model();

        self::$traderOrder = InProgressOrder::of(self::$order)->createTraderOrder('fake');

        TraderOrderScenario::of(self::$traderOrder)
            ->moveToStep(MurabhaStep::MurabhaOfferIssued);

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

    /**
     * @return void
     */
    public function test_auth_user_cant_get_murabaha_purchase_offer_with_invalid_permissions(): void
    {
        $this->assertStatusCodeExceptForPermissions(Response::HTTP_FORBIDDEN,
            [
                Area::Trader => [
                    [Subject::All, Action::Manage],
                    [Subject::FinancingOrders, Action::Edit],
                    [Subject::FinancingOrders, Action::Manage],
                ],
            ], function ($user, $role, $permission) {
                return $this->actingAs($user)
                    ->withHeader('X-Company', self::$company->id)
                    ->getJson(self::$apiUrl);
            });
    }
}
