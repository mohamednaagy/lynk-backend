<?php

namespace Endpoints\Api\V1\Admin\Lenders\Orders\TraderOrders\PurchasingCommodity;

use App\Enums\Area;
use App\Enums\ErrorCode;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
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
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class UpdatePurchasingCommodityTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    const BaseUrl = 'api/v1/admin';

    private static Company $lender;

    private static User $userLender;

    private static User $superAdminUser;

    private static Builder|Model $financingOrder;

    private static TraderOrder $traderOrder;

    private static string $updatePurchasingCommodityUrl;

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

        self::$superAdminUser = $this->createSuperAdminUser();

        [self::$lender] = $this->createLenderCompany('2000', [
            'company_cr' => '1234567891',
        ]);
        self::$userLender = $this->createLenderUser(self::$lender->id);

        self::$financingOrder = $this->createOrder(
            self::$lender->id,
            self::$userLender->id,
            [
                'is_verification_required' => true,
                'status' => FinancingOrderStatus::WaitingPurchasingCommodity,
            ]
        );

        // create trader order
        self::$traderOrder = self::$financingOrder->traderOrders()->create([
            'provider' => 'dmcc',
            'reference' => '123456789',
            'status' => TraderOrderStatus::InProgress,
        ]);

        self::$updatePurchasingCommodityUrl = self::BaseUrl.
            '/orders/'.
            self::$financingOrder->getOriginal('id').
            '/trader-orders/'.
            self::$traderOrder->getOriginal('id').
            '/purchasing-commodity';

        self::$requestData = [
            'ptp_document' => UploadedFile::fake()->create('attachment.pdf', 10),
            'original_holding_certificate' => UploadedFile::fake()->create('attachment.pdf', 10),
            'financing_institution_certificate' => UploadedFile::fake()->create('attachment.pdf', 10),
            'product' => 'product',
            'quantity' => 100,
            'amount' => 100,
            'currency' => 'currency',
            'warehouse' => 'warehouse',
            'owner' => 'owner',
            'previous_owner' => 'previous_owner',
            'date_time_of_purchasing_commodity' => '2023-02-21 09:30:00',
            'warehouse_or_vault_emirates' => 'dummy',
            'warehouse_or_vault_country' => 'dummy',
            'uom' => 'dummy',
            'exchange_rate' => 10,
            'auto_generate_financing_institution_certificate' => 0,
        ];
    }

    /**
     * @return void
     */
    public function test_that_unauth_user_cant_update_purchasing_commodity(): void
    {
        $this->postJson(self::$updatePurchasingCommodityUrl, self::$requestData)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_other_area_roles_of_not_super_admin_area_cant_update_purchasing_commodity(): void
    {
        $this->assertStatusCodeForAllRolesExceptForArea(
            Response::HTTP_FORBIDDEN,
            [
                Area::SuperAdmin,
            ],
            function ($user, $role) {
                return $this->actingAs($user)
                    ->postJson(self::$updatePurchasingCommodityUrl, self::$requestData);
            }
        );
    }

    /**
     * @return void
     */
    public function test_proceed_purchasing_commodity_is_successfull_and_order_status_will_be_updated(): void
    {
        // create trader order history of previous last step
        self::$traderOrder->traderHistories()->create(
            [
                'action' => FinancingOrderHistory::$orderHistoryLastActionMap[FinancingOrderStatus::WaitingPurchasingCommodity],
            ]
        );

        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$updatePurchasingCommodityUrl, self::$requestData)
            ->assertJsonStructure([
                'data' => [
                    'purchasing_commodity_information',
                ],
            ]);

        $freshOrderStatus = self::$financingOrder->fresh()->status;

        $this->assertTrue($freshOrderStatus->is(FinancingOrderStatus::CommodityPurchased));
    }

    /**
     * @dataProvider unsuitableTraderHistoryDataProvider
     *
     * @param $unsuitableTraderHistoryData
     * @return void
     */
    public function test_update_purchasing_commodity_not_follow_sequence($unsuitableTraderHistoryData): void
    {
        self::$traderOrder->traderHistories()->create([
            'action' => $unsuitableTraderHistoryData,
        ]);

        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$updatePurchasingCommodityUrl, self::$requestData)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => __('error.order_status_doesnt_follow_sequence'),
                'code' => ErrorCode::ORDER_STATUS_DOESNT_FOLLOW_SEQUENCE,
            ]);
    }

    public function unsuitableTraderHistoryDataProvider(): array
    {
        return [
            'histories_that_doesnt_follow_sequence' => collect(FinancingOrderHistory::getValues())
                ->reject(function ($item) {
                    return $item == FinancingOrderHistory::$orderHistoryLastActionMap[FinancingOrderStatus::WaitingPurchasingCommodity]
                        || $item == FinancingOrderHistory::$orderHistoryLastActionMap[FinancingOrderStatus::CommodityPurchased];
                })->toArray(),
        ];
    }

    /**
     * @return void
     */
    public function test_update_purchasing_commodity_is_successful_and_order_status_will_not_be_updated(): void
    {
        self::$traderOrder->traderHistories()->create([
            'action' => FinancingOrderHistory::$orderHistoryLastActionMap[FinancingOrderStatus::WaitingPurchasingCommodity],
        ]);

        self::$financingOrder->update([
            'status' => FinancingOrderStatus::CommodityPurchased,
        ]);

        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$updatePurchasingCommodityUrl, self::$requestData)
            ->assertJsonStructure([
                'data' => [
                    'purchasing_commodity_information',
                ],
            ]);

        $freshOrderStatus = self::$financingOrder->fresh()->status;

        $this->assertTrue($freshOrderStatus->is(FinancingOrderStatus::CommodityPurchased));
    }
}
