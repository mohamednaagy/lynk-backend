<?php

namespace Endpoints\Api\V1\Trader\FinancingOrders\TraderOrders\MurabhaCompleteDocument;

use App\Enums\Area;
use App\Enums\ErrorCode;
use App\Enums\FinancingOrderHistory;
use App\Enums\MurabhaStep;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\TraderOrder;
use App\Models\User;
use App\Support\Sms\Events\SmsSent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\Support\FinancingOrders\TraderOrderScenario;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class UpdateMurabhaCompleteDocumentTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    const BaseUrl = 'api/v1/trader';

    private static Company $trader;

    private static Company $lender;

    private static User $userLender;

    private static User $traderAdminUser;

    private static Builder|Model $financingOrder;

    private static Model|TraderOrder $traderOrder;

    private static string $updateMurabhaCompleteDocumentUrl;

    private static array $requestData;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        Event::fake([
            SmsSent::class,
        ]);

        [self::$trader] = $this->createTraderCompany('2000', [
            'company_cr' => '1234567891',
            'driver' => 'fake',
        ]);
        [self::$lender] = $this->createLenderCompany('2000', [
            'company_cr' => '1234567892',
        ]);
        self::$traderAdminUser = $this->createTraderUser(self::$trader->id);
        self::$userLender = $this->createLenderUser(self::$lender->id);
        self::$financingOrder = OrderScenario::inProgress()
            ->lender(self::$lender)
            ->creator(self::$userLender)
            ->commit()
            ->model();

        // create trader order
        self::$traderOrder = InProgressOrder::of(self::$financingOrder)->createTraderOrder(self::$trader->driver);

        TraderOrderScenario::of(self::$traderOrder)->moveToStep(MurabhaStep::MurabhaOfferIssued);

        self::$updateMurabhaCompleteDocumentUrl = self::BaseUrl.
            '/orders/'.
            self::$financingOrder->id.
            '/trader-orders/'.
            self::$traderOrder->id.
            '/murabha-complete';

        self::$requestData = [
            'document' => UploadedFile::fake()
                ->create('attachment.pdf', 10),
        ];
    }

    /**
     * @return void
     */
    public function test_that_unauth_user_cant_update_murabha_complete_document(): void
    {
        $this->withHeader('X-Company', self::$trader->id)
            ->postJson(self::$updateMurabhaCompleteDocumentUrl, self::$requestData)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_other_area_roles_of_not_trader_area_cant_update_murabha_complete_document(): void
    {
        $this->assertStatusCodeForAllRolesExceptForArea(
            Response::HTTP_FORBIDDEN,
            [
                Area::Trader,
            ],
            function ($user, $role) {
                return $this->withHeader('X-Company', self::$trader->id)
                    ->actingAs($user)
                    ->postJson(self::$updateMurabhaCompleteDocumentUrl, self::$requestData);
            }
        );
    }

    /**
     * @return void
     */
    public function test_proceed_murabha_complete_document_succeed(): void
    {
        $this->withHeader('X-Company', self::$trader->id)
            ->actingAs(self::$traderAdminUser)
            ->postJson(self::$updateMurabhaCompleteDocumentUrl, self::$requestData)
            ->assertJsonStructure(['data']);

        $freshOrderStatus = self::$financingOrder->fresh()->status;
        $this->assertTrue(self::$traderOrder->doesLastActionMatchWith(FinancingOrderHistory::MurabahaSaleCompleted));

        $freshTraderOrderStatus = self::$traderOrder->fresh()->status;
        $this->assertTrue($freshTraderOrderStatus->is(TraderOrderStatus::Completed));
    }

    /**
     * @dataProvider unsuitableTraderHistoryDataProvider
     *
     * @param $unsuitableTraderHistoryData
     * @return void
     */
    public function test_update_murabha_complete_document_not_follow_sequence($unsuitableTraderHistoryData): void
    {
        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToHistory($unsuitableTraderHistoryData);

        $this->withHeader('X-Company', self::$trader->id)
            ->actingAs(self::$traderAdminUser)
            ->postJson(self::$updateMurabhaCompleteDocumentUrl, self::$requestData)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => __('error.order_status_doesnt_follow_sequence'),
                'code' => ErrorCode::ORDER_STATUS_DOESNT_FOLLOW_SEQUENCE,
            ]);
    }

    public function unsuitableTraderHistoryDataProvider()
    {
        return [
            'histories_that_doesnt_follow_sequence' => collect(FinancingOrderHistory::getValues())
                ->reject(function ($item) {
                    return in_array($item, [FinancingOrderHistory::AttachMpoDocument, FinancingOrderHistory::GetTtiId, FinancingOrderHistory::OrderCancelled, FinancingOrderHistory::Expired]);
                })
                ->toArray(),
        ];
    }
}
