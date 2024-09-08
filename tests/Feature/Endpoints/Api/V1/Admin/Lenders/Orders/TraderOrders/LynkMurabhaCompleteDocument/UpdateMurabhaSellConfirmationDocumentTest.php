<?php

namespace Endpoints\Api\V1\Admin\Lenders\Orders\TraderOrders\LynkMurabhaCompleteDocument;

use App\Enums\Area;
use App\Enums\ErrorCode;
use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\MurabhaStep;
use App\Enums\Trader;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\TraderOrder;
use App\Models\User;
use App\Support\Sms\Events\SmsSent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Support\FinancingOrders\CommittedOrder;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\Support\FinancingOrders\TraderOrderScenario;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;
use Tests\Traits\InteractsWithSupplier;

class UpdateMurabhaSellConfirmationDocumentTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea, InteractsWithSupplier;

    const BaseUrl = 'api/v1/admin';

    private static Company $lender;

    private static User $userLender;

    private static User $superAdminUser;

    private static CommittedOrder $financingOrder;

    private static Model|TraderOrder $traderOrder;

    private static string $updateMurabhaCompleteDocumentUrl;

    private static array $requestData;

    public function setUp(): void
    {
        parent::setUp();

        self::$superAdminUser = $this->createSuperAdminUser();

        [self::$lender] = $this->createLenderCompany('2000', [
            'company_cr' => '1234567891',
        ]);

        self::$userLender = $this->createLenderUser(self::$lender->id);

        self::$financingOrder = OrderScenario::inProgress()
            ->creator(self::$userLender)
            ->commit();

        self::$traderOrder = InProgressOrder::of(self::$financingOrder)->createTraderOrder(Trader::Lynk);

        self::$updateMurabhaCompleteDocumentUrl = self::BaseUrl.
            '/orders/'.
            self::$financingOrder->id.
            '/trader-orders/'.
            self::$traderOrder->id.
            '/attach-sell-confirmation-document';

        self::$requestData = [
            'sell_confirmation_document' => UploadedFile::fake()
                ->create('attachment.pdf', 10),
        ];

    }

    public function test_that_unauth_user_cant_update_murabha_complete_document(): void
    {
        $this->postJson(self::$updateMurabhaCompleteDocumentUrl, self::$requestData)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_that_other_area_roles_of_not_super_admin_area_cant_update_murabha_complete_document(): void
    {
        TraderOrderScenario::of(self::$traderOrder)
        ->reset()
        ->moveToStep(MurabhaStep::MurabahaSaleCompleted);

    $this->actingAs(self::$userLender)
        ->postJson(self::$updateMurabhaCompleteDocumentUrl, self::$requestData)
        ->assertForbidden();
    }

    public function test_proceed_murabha_complete_document_is_successful_and_order_status_will_be_updated(): void
    {
        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToStep(MurabhaStep::MurabahaSaleCompleted);

        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$updateMurabhaCompleteDocumentUrl, self::$requestData)
            ->assertJsonStructure(['data']);

        $this->assertTrue(self::$traderOrder->hasMedia(TraderOrderMediaCollection::SellConfirmationDocument));
    }

    /**
     * @dataProvider unsuitableTraderHistoryDataProvider
     */
    public function test_update_murabha_complete_document_not_follow_sequence($action)
    {
        Queue::fake();
        self::$traderOrder->traderHistories()->create([
            'action' => $action,
        ]);

        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$updateMurabhaCompleteDocumentUrl, self::$requestData)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => __('error.order_status_doesnt_follow_sequence'),
                'code' => ErrorCode::ORDER_STATUS_DOESNT_FOLLOW_SEQUENCE,
            ]);
    }

    public function unsuitableTraderHistoryDataProvider(): array
    {
        $histories = collect(FinancingOrderHistory::getValues())
            ->reject(function ($item) {
                return $item == FinancingOrderHistory::AttachMpoDocument
                    || $item == FinancingOrderHistory::MurabahaSaleCompleted;
            });

        $histories->transform(function ($history) {
            return [$history];
        });

        return $histories->toArray();
    }

    public function test_update_murabha_complete_document_is_successful(): void
    {
        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToStep(MurabhaStep::MurabahaSaleCompleted);

        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$updateMurabhaCompleteDocumentUrl, self::$requestData)
            ->assertJsonStructure(['data']);

        $this->assertTrue(self::$traderOrder->hasMedia(TraderOrderMediaCollection::SellConfirmationDocument));

    }
}
