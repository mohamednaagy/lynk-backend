<?php

namespace Endpoints\Api\V1\Admin\Lenders\Orders\TraderOrders\MurabhaPurchaseOffer;

use App\Enums\Area;
use App\Enums\ErrorCode;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\TraderOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class UpdateMurabhaPurchaseOfferTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    const BaseUrl = 'api/v1/admin';

    private static Company $lender;

    private static User $userLender;

    private static User $superAdminUser;

    private static Builder|Model $financingOrder;

    private static TraderOrder $traderOrder;

    private static string $updateMurabhaPurchaseOfferUrl;

    private static array $requestData;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        Artisan::call('module:seed');

        self::$superAdminUser = $this->createSuperAdminUser();
        [self::$lender] = $this->createLenderCompany('2000', ['company_cr' => '1234567891']);
        self::$userLender = $this->createLenderUser(self::$lender->id);
        self::$financingOrder = $this->createOrder(
            self::$lender->id,
            self::$userLender->id,
            [
                'is_verification_required' => true,
                'status' => FinancingOrderStatus::InProgress,
            ]
        );

        // create trader order
        self::$traderOrder = self::$financingOrder->traderOrders()->create([
            'provider' => 'dmcc',
            'reference' => '123456789',
            'status' => TraderOrderStatus::InProgress,
        ]);

        self::$updateMurabhaPurchaseOfferUrl = self::BaseUrl.
            '/orders/'.
            self::$financingOrder->getOriginal('id').
            '/trader-orders/'.
            self::$traderOrder->getOriginal('id').
            '/murabha-purchase-offer';

        self::$requestData = [
            'document' => UploadedFile::fake()
                ->create('attachment.pdf', 10),
        ];
    }

    /**
     * @return void
     */
    public function test_that_unauth_user_cant_update_murabha_purchase_offer(): void
    {
        $this->postJson(self::$updateMurabhaPurchaseOfferUrl, self::$requestData)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
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

    /**
     * @return void
     */
    public function test_proceed_murabha_purchase_offer_document_is_successfull_and_order_status_will_be_updated(): void
    {
        // create trader order history of previous step

        self::$traderOrder->traderHistories()->create(
            [
                'action' => FinancingOrderHistory::$orderHistoryLastActionMap[FinancingOrderStatus::ClientWakalaCompleted],
            ]
        );

        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$updateMurabhaPurchaseOfferUrl, self::$requestData)
            ->assertJsonStructure(['data']);

        $freshOrderStatus = self::$financingOrder->fresh()->status;

        $this->assertTrue($freshOrderStatus->is(FinancingOrderStatus::MurabhaOfferIssued));
    }

    /**
     * @return void
     */
    public function test_update_murabha_purchase_offer_document_not_follow_sequence(): void
    {
        self::$traderOrder->traderHistories()->create(
            [
                'action' => FinancingOrderHistory::CreateSellingCommodityToCustomerDocument,
            ]
        );

        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$updateMurabhaPurchaseOfferUrl, self::$requestData)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => __('error.order_status_doesnt_follow_sequence'),
                'code' => ErrorCode::ORDER_STATUS_DOESNT_FOLLOW_SEQUENCE,
            ]);
    }

    /**
     * @return void
     */
    public function test_update_murabha_purchase_offer_document_is_successful_and_order_status_will_not_be_updated(): void
    {
        self::$traderOrder->traderHistories()->create(
            [
                'action' => FinancingOrderHistory::$orderHistoryLastActionMap[FinancingOrderStatus::ClientWakalaCompleted],
            ]
        );

        self::$traderOrder->traderHistories()->create([
            'action' => FinancingOrderHistory::$orderHistoryLastActionMap[FinancingOrderStatus::MurabahaSaleCompleted],
        ]);

        self::$financingOrder->update([
            'status' => FinancingOrderStatus::MurabhaOfferIssued,
        ]);

        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$updateMurabhaPurchaseOfferUrl, self::$requestData)
            ->assertJsonStructure(['data']);

        $freshOrderStatus = self::$financingOrder->fresh()->status;

        $this->assertTrue($freshOrderStatus->is(FinancingOrderStatus::MurabhaOfferIssued));
    }
}
