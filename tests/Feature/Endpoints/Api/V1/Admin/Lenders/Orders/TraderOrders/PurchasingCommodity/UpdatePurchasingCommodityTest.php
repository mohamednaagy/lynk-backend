<?php

namespace Endpoints\Api\V1\Admin\Lenders\Orders\TraderOrders\PurchasingCommodity;

use App\Enums\Area;
use App\Enums\ErrorCode;
use App\Enums\MurabhaStep;
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
use Tests\Support\FinancingOrders\CommittedOrder;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\Support\FinancingOrders\TraderOrderScenario;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class UpdatePurchasingCommodityTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    const BaseUrl = 'api/v1/admin';

    private static Company $lender;

    private static User $userLender;

    private static User $superAdminUser;

    private static CommittedOrder $financingOrder;

    private static Builder|Model|TraderOrder $traderOrder;

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

        self::$financingOrder = OrderScenario::inProgress()
            ->creator(self::$userLender)
            ->commit();

        self::$traderOrder = InProgressOrder::of(self::$financingOrder)->createTraderOrder();

        self::$updatePurchasingCommodityUrl = self::BaseUrl.
            '/orders/'.
            self::$financingOrder->id.
            '/trader-orders/'.
            self::$traderOrder->id.
            '/purchasing-commodity';

        self::$requestData = [
            'products' => [
                [
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
                ],
            ],
            'ptp_document' => UploadedFile::fake()->create('attachment.pdf', 10),
            'original_holding_certificate' => UploadedFile::fake()->create('attachment.pdf', 10),
            'financing_institution_certificate' => UploadedFile::fake()->create('attachment.pdf', 10),
            'auto_generate_financing_institution_certificate' => 0,
            'exchange_rate' => 10,
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
        TraderOrderScenario::of(self::$traderOrder)
            ->reset();

        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$updatePurchasingCommodityUrl, self::$requestData)
            ->assertJsonStructure([
                'data' => [
                    'purchasing_commodity_information',
                ],
            ]);

        $this->assertEquals(MurabhaStep::PurchasingCommodity, self::$traderOrder->append('step')->step);
    }

    public function test_update_purchasing_commodity_not_follow_sequence(): void
    {
        self::$traderOrder->traderHistories()->delete();

        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$updatePurchasingCommodityUrl, self::$requestData)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => __('error.order_status_doesnt_follow_sequence'),
                'code' => ErrorCode::ORDER_STATUS_DOESNT_FOLLOW_SEQUENCE,
            ]);
    }

    /**
     * @return void
     */
    public function test_update_purchasing_commodity_is_successful_and_order_status_will_not_be_updated(): void
    {
        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToStep(MurabhaStep::PurchasingCommodity);

        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$updatePurchasingCommodityUrl, self::$requestData)
            ->assertJsonStructure([
                'data' => [
                    'purchasing_commodity_information',
                ],
            ]);

        $this->assertEquals(MurabhaStep::PurchasingCommodity, self::$traderOrder->append('step')->step);
    }
}
