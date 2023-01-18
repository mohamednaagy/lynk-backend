<?php

namespace Jobs\General;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\TraderOrderStatus;
use App\Jobs\Dmcc\ProcessDmccPtpNotification;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
        self::$financingOrder = $this->createOrder(self::$company->id, self::$lender->id, [
            'status' => FinancingOrderStatus::WaitingPurchasingCommodity,
        ]);

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

        $ttiId = self::$notification->notificationHeaderAndEntity->notificationEntityDetails->
        notificationEntity[0]->entityValue;

        self::$traderOrder = self::$financingOrder->traderOrders()->create([
            'provider' => 'fake',
            'reference' => $ttiId,
            'status' => TraderOrderStatus::InProgress,
        ]);
    }

    public function test_job_not_processed_if_active_trader_order_has_invalid_provider()
    {
        self::$traderOrder->update(['provider' => 'invalid']);

        $process = new ProcessDmccPtpNotification(self::$notification);
        $process->handle();

        $this->assertTrue(
            self::$financingOrder->fresh()->status->is(FinancingOrderStatus::WaitingPurchasingCommodity)
        );
    }

    public function test_job_processed_if_active_trader_order_has_valid_provider()
    {
        Http::fake();
        $process = new ProcessDmccPtpNotification(self::$notification);
        $process->handle();

        $this->assertTrue(
            self::$financingOrder->fresh()->status->is(FinancingOrderStatus::RespondedToPtp)
        );
    }

    public function test_job_not_processed_if_current_financing_order_is_unsuitable_status()
    {
        self::$financingOrder->update(['status' => FinancingOrderStatus::Cancelled]);

        $process = new ProcessDmccPtpNotification(self::$notification);
        $process->handle();

        $this->assertFalse(
            self::$financingOrder->refresh()->status->is(FinancingOrderStatus::RespondedToPtp)
        );
    }

    public function test_job_not_processed_if_not_responded_to_ptp()
    {
        Http::fake();
        config()->set('trader.providers.fake.url', 'invalidRespondPTPServiceUrl');
        self::$traderOrder->update(['provider' => 'deafProviderCannotRespond']);

        $process = new ProcessDmccPtpNotification(self::$notification);
        $process->handle();

        $this->assertFalse(
            self::$financingOrder->fresh()->status->is(FinancingOrderStatus::RespondedToPtp)
        );
    }

    public function test_job_not_trader_order_history_is_created_with_RespondPtp_status()
    {
        Http::fake();
        $process = new ProcessDmccPtpNotification(self::$notification);
        $process->handle();

        $this->assertDatabaseCount('trader_histories', 1)
            ->assertDatabaseHas('trader_histories', [
                'trader_order_id' => self::$traderOrder->id,
                'action' => FinancingOrderHistory::RespondPtp,
            ]);
    }

    public function test_job_processed_and_order_status_updated_to_RespondedToPtp_status()
    {
        Http::fake();
        $process = new ProcessDmccPtpNotification(self::$notification);
        $process->handle();

        $this->assertTrue(
            self::$financingOrder->fresh()->status->is(FinancingOrderStatus::RespondedToPtp)
        );
    }
}
