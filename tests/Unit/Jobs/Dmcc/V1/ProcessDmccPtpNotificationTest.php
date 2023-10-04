<?php

namespace Jobs\Dmcc\V1;

use App\Enums\FinancingOrderHistory;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\User;
use App\Support\Traders\Drivers\Dmcc\Jobs\V1\ProcessDmccPtpNotification;
use CodeDredd\Soap\Facades\Soap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\Support\FinancingOrders\TraderOrderScenario;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class ProcessDmccPtpNotificationTest extends TestCase
{
    use RefreshDatabase, InteractsWithCompany, InteractsWithUser;

    protected static Company $company;

    protected static User $lender;

    protected static FinancingOrder $financingOrder;

    protected static Model|TraderOrder $traderOrder;

    protected static mixed $notification;

    protected function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany();
        self::$lender = $this->createLenderUser(self::$company->id);

        self::$financingOrder = OrderScenario::inProgress()
            ->lender(self::$company)
            ->creator(self::$lender)
            ->commit()
            ->model();

        self::$notification = (object) [
            'notificationHeaderAndEntity' => (object) [
                'notificationEntityDetails' => (object) [
                    'notificationEntity' => [
                        0 => (object) [
                            'entityValue' => '123456789',
                        ],
                    ],
                ],
            ],
        ];

        $ttiId = self::$notification->notificationHeaderAndEntity
            ->notificationEntityDetails
            ->notificationEntity[0]
            ->entityValue;

        self::$traderOrder = InProgressOrder::of(self::$financingOrder)->createTraderOrder('fake', '123456789');
    }

    public function test_job_not_processed_if_active_trader_order_has_invalid_provider()
    {
        self::$traderOrder->update(['provider' => 'invalid']);

        $process = new ProcessDmccPtpNotification(self::$notification);
        $process->handle();

        $this->assertTrue(
            self::$traderOrder->doesLastActionMatchWith(FinancingOrderHistory::GetTtiId)
        );
    }

    public function test_job_will_processed_if_active_trader_order_has_fake_provider()
    {
        Http::fake();

        $process = new ProcessDmccPtpNotification(self::$notification);
        $process->handle();

        $this->assertTrue(
            self::$traderOrder->doesLastActionMatchWith(FinancingOrderHistory::RespondPtp)
        );
    }

    public function test_job_processed_if_active_trader_order_has_dmcc_provider()
    {
        Soap::fake(function () {
            return Soap::response([
                'successCode' => '0000',
            ]);
        });

        self::$traderOrder->update(['provider' => 'dmcc']);

        $process = new ProcessDmccPtpNotification(self::$notification);
        $process->handle();

        $this->assertTrue(
            self::$traderOrder->doesLastActionMatchWith(FinancingOrderHistory::RespondPtp)
        );
    }

    /**
     * @dataProvider unsuitableOrderStatusDataProvider
     */
    public function test_job_not_processed_if_current_financing_order_is_unsuitable_status($unsuitableOrderStatusData)
    {
        dump($unsuitableOrderStatusData);

        self::$traderOrder = TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToHistory($unsuitableOrderStatusData)
            ->getTraderOrder();

        $process = new ProcessDmccPtpNotification(self::$notification);
        $process->handle();

        $this->assertFalse(
            self::$traderOrder->doesLastActionMatchWith(FinancingOrderHistory::RespondPtp)
        );
    }

    public function unsuitableOrderStatusDataProvider()
    {
        return collect(FinancingOrderHistory::getValues())->reject(function ($item) {
            return in_array($item, [
                FinancingOrderHistory::RespondPtp, FinancingOrderHistory::GetTtiId, FinancingOrderHistory::OrderCancelled, FinancingOrderHistory::Expired,
                FinancingOrderHistory::CommoditySoldToMarket, FinancingOrderHistory::GetOwnershipToCustomerCertificate, FinancingOrderHistory::GetSellingToMarketCertificate, // not exists in DMCC steps
            ]);
        })->map(function ($item) {
            return [$item];
        })->toArray();
    }

    public function test_job_trader_order_history_is_created_with_respond_ptp_status()
    {
        Http::fake();
        $process = new ProcessDmccPtpNotification(self::$notification);
        $process->handle();

        $this->assertDatabaseCount('trader_histories', 2)
            ->assertDatabaseHas('trader_histories', [
                'trader_order_id' => self::$traderOrder->id,
                'action' => FinancingOrderHistory::RespondPtp,
            ]);
    }

    public function test_job_processed_and_order_status_updated_to_responded_to_ptp_status()
    {
        Http::fake();
        $process = new ProcessDmccPtpNotification(self::$notification);
        $process->handle();

        $this->assertTrue(
            self::$traderOrder->checkOrderHistoryAction(FinancingOrderHistory::RespondPtp)
        );
    }
}
