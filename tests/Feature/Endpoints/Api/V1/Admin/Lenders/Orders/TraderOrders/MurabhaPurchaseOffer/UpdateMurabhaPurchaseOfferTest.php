<?php

namespace Endpoints\Api\V1\Admin\Lenders\Orders\TraderOrders\MurabhaPurchaseOffer;

use App\Enums\Area;
use App\Enums\ErrorCode;
use App\Enums\MurabhaStep;
use App\Models\Company;
use App\Models\TraderOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Tests\Support\FinancingOrders\CommittedOrder;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\Support\FinancingOrders\TraderOrderScenario;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class UpdateMurabhaPurchaseOfferTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    const BaseUrl = 'api/v1/admin';

    private static Company $lender;

    private static User $userLender;

    private static User $superAdminUser;

    private static CommittedOrder $financingOrder;

    private static Model|TraderOrder $traderOrder;

    private static string $updateMurabhaPurchaseOfferUrl;

    private static array $requestData;

    public function setUp(): void
    {
        parent::setUp();

        Artisan::call('module:seed');

        self::$superAdminUser = $this->createSuperAdminUser();
        [self::$lender] = $this->createLenderCompany('2000', ['company_cr' => '1234567891']);
        self::$userLender = $this->createLenderUser(self::$lender->id);
        self::$financingOrder = OrderScenario::inProgress()
            ->creator(self::$userLender)
            ->commit();

        self::$traderOrder = InProgressOrder::of(self::$financingOrder)->createTraderOrder();

        self::$updateMurabhaPurchaseOfferUrl = self::BaseUrl.
            '/orders/'.
            self::$financingOrder->id.
            '/trader-orders/'.
            self::$traderOrder->id.
            '/murabha-purchase-offer';

        self::$requestData = [
            'document' => UploadedFile::fake()
                ->create('attachment.pdf', 10),
        ];
    }

    public function test_that_unauth_user_cant_update_murabha_purchase_offer(): void
    {
        $this->postJson(self::$updateMurabhaPurchaseOfferUrl, self::$requestData)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_that_other_area_roles_of_not_super_admin_area_cant_update_murabha_purchase_offer_document(): void
    {
        $this->assertStatusCodeForAllRolesExceptForArea(
            Response::HTTP_FORBIDDEN,
            [
                Area::SuperAdmin,
            ],
            function ($user, $role) {
                return $this->actingAs($user)
                    ->postJson(self::$updateMurabhaPurchaseOfferUrl, self::$requestData);
            }
        );
    }

    public function test_proceed_murabha_purchase_offer_document_is_successfull(): void
    {
        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToStep(MurabhaStep::ClientWakala);

        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$updateMurabhaPurchaseOfferUrl, self::$requestData)
            ->assertJsonStructure(['data']);

        $this->assertEquals(MurabhaStep::MurabhaOfferIssued, self::$traderOrder->currentStep);
    }

    public function test_update_murabha_purchase_offer_document_not_follow_sequence(): void
    {
        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToStep(MurabhaStep::PurchasingCommodity);

        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$updateMurabhaPurchaseOfferUrl, self::$requestData)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => __('error.order_status_doesnt_follow_sequence'),
                'code' => ErrorCode::ORDER_STATUS_DOESNT_FOLLOW_SEQUENCE,
            ]);
    }

    public function test_update_murabha_purchase_offer_document_is_successful_and_order_status_will_not_be_updated(): void
    {
        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToStep(MurabhaStep::MurabhaOfferIssued);

        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$updateMurabhaPurchaseOfferUrl, self::$requestData)
            ->assertJsonStructure(['data']);

        $this->assertEquals(MurabhaStep::MurabhaOfferIssued, self::$traderOrder->currentStep);
    }
}
