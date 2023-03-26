<?php

namespace Endpoints\Api\V1\Trader\FinancingOrders\TraderOrders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\FinancingOrderHistory;
use App\Enums\MurabhaStep;
use App\Enums\Subject;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\Support\FinancingOrders\TraderOrderScenario;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class UpdateMurabahaPurchaseOfferTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    private static Company $company;

    private static User $traderAdminUser;

    private static FinancingOrder|Model $order;

    private static TraderOrder|Model $traderOrder;

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

        TraderOrderScenario::of(self::$traderOrder)->moveToStep(MurabhaStep::ClientWakala);

        self::$apiUrl = 'api/v1/trader/orders/'.self::$order->id.'/trader-orders/'.self::$traderOrder->id.'/murabaha-purchase-offer';
    }

    /**
     * @return void
     */
    public function test_unauth_user_cannot_access(): void
    {
        $this->withHeader('X-Company', self::$company->id)
            ->postJson(self::$apiUrl)
            ->assertUnauthorized();
    }

    public function test_trader_admin_cannot_proceed_with_murabaha_purchase_offer_if_previous_step_is_not_complete(): void
    {
        self::$traderOrder->traderHistories()->delete();

        $this->actingAs(self::$traderAdminUser)
            ->withHeader('X-Company', self::$company->id)
            ->postJson(self::$apiUrl, [
                'document' => UploadedFile::fake()->create('test.pdf'),
            ])
            ->assertStatus(400)
            ->assertJsonPath('code', 1011);
    }

    public function test_trader_admin_can_proceed_with_murabaha_purchase_offer_successful(): void
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

        $this->assertTrue(self::$traderOrder->doesLastActionMatchWith(FinancingOrderHistory::AttachMpoDocument));
    }

    public function test_trader_admin_can_update_murabaha_purchase_offer_successful(): void
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

        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToStep(MurabhaStep::MurabahaSaleCompleted);

        $this->actingAs(self::$traderAdminUser)
            ->withHeader('X-Company', self::$company->id)
            ->postJson(self::$apiUrl, [
                'document' => UploadedFile::fake()->create('test.pdf'),
            ])
            ->assertOk()
            ->assertJsonStructure([
                'data' => [],
            ]);

        $this->assertTrue(self::$traderOrder->doesLastActionMatchWith(FinancingOrderHistory::MurabahaSaleCompleted));
    }

    public function test_trader_admin_can_update_murabaha_purchase_offer_when_trader_order_status_not_in_progress(): void
    {
        self::$traderOrder->update(['status' => TraderOrderStatus::Completed]);
        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToStep(MurabhaStep::MurabahaSaleCompleted);

        $this->actingAs(self::$traderAdminUser)
            ->withHeader('X-Company', self::$company->id)
            ->postJson(self::$apiUrl, [
                'document' => UploadedFile::fake()->create('test.pdf'),
            ])
            ->assertOk()
            ->assertJsonStructure([
                'data' => [],
            ]);

        $this->assertTrue(self::$traderOrder->doesLastActionMatchWith(FinancingOrderHistory::MurabahaSaleCompleted));
    }

    public function test_other_users_roles_not_in_trader_area_can_not_update_process_murabaha_purchase_offer_with_invalid_permissions()
    {
        $this->assertStatusCodeExceptForPermissions(
            Response::HTTP_FORBIDDEN,
            [
                Area::Trader => [
                    [Subject::All, Action::Manage],
                    [Subject::FinancingOrders, Action::Edit],
                    [Subject::FinancingOrders, Action::Manage],
                ],
            ],
            function ($user, $role, $permission) {
                return $this->actingAs($user)
                    ->withHeader('X-Company', self::$company->id)
                    ->postJson(self::$apiUrl);
            }
        );
    }
}
