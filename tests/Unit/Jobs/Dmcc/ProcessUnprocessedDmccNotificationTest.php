<?php

namespace Tests\Unit\Jobs\General;

use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\TraderOrderStatus;
use App\Exceptions\TraderException;
use App\Jobs\Dmcc\ProcessUnprocessedDmccNotification;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\User;
use App\Support\Traders\Facades\Trader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class ProcessUnprocessedDmccNotificationTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    protected static Company $company;

    protected static User $lender;

    protected static $notification;

    protected static $traderOrder;

    protected static FinancingOrder $order;

    protected function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany();
        self::$lender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$order = $this->createOrder(self::$company->id, self::$lender->id, [
            'status' => FinancingOrderStatus::MurabahaSaleCompleted,
        ]);

        $trader = Trader::driver('fake');

        $notifications = collect($trader->fetchNotifications('ACTIONABLE'));
        self::$notification = $notifications->first();
        self::$notification->notificationHeaderAndEntity->notificationId;
        $ttiId = self::$notification->notificationHeaderAndEntity->notificationEntityDetails->notificationEntity[0]->entityValue;

        self::$traderOrder = TraderOrder::create([
            'status' => TraderOrderStatus::InProgress,
            'financing_order_id' => self::$order->id,
            'reference' => $ttiId,
            'provider' => 'fake',

        ]);
    }

    public function test_process_unprocessed_dmcc_notification_job_will_be_processed_if_the_active_trader_has_dmcc_or_fake_as_provider()
    {
        config()->set('trader.default', 'wrong provider');
        Bus::fake();

        (new ProcessUnprocessedDmccNotification(self::$notification))->handle();

        self::$traderOrder->refresh();

        $this->assertFalse(self::$traderOrder->status->is(TraderOrderStatus::Completed));
    }

    public function test_process_unprocessed_dmcc_notification_job_will_be_processed_if_the_current_order_status_is_MurabahaSaleCompleted()
    {
        Bus::fake();

        (new ProcessUnprocessedDmccNotification(self::$notification))->handle();
        self::$traderOrder->refresh();

        $this->assertTrue(self::$traderOrder->status->is(TraderOrderStatus::Completed));
    }

    public function test_process_unprocessed_dmcc_notification_processNotification_is_called_successfully()
    {
        $this->expectException(TraderException::class);
        config()->set('trader.providers.fake.username', 'wrong username');

        (new ProcessUnprocessedDmccNotification(self::$notification))->handle();
    }

    public function test_process_unprocessed_dmcc_notification_trader_status_is_updated_to_completed()
    {
        Bus::fake();

        (new ProcessUnprocessedDmccNotification(self::$notification))->handle();

        self::$traderOrder->refresh();

        $this->assertTrue(self::$traderOrder->status->is(TraderOrderStatus::Completed));
    }
}
