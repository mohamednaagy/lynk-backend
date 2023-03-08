<?php

namespace Tests\Unit\Jobs\Dmcc;

use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\TraderOrderStatus;
use App\Exceptions\TraderException;
use App\Exceptions\TraderNotSupported;
use App\Jobs\Dmcc\ProcessUnprocessedDmccNotification;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Fluent;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class ProcessUnprocessedDmccNotificationTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    protected static Company $company;

    protected static User $lender;

    protected static $notification;

    protected static $traderOrder;

    protected static $ttiId;

    protected static FinancingOrder $order;

    protected function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany();
        self::$lender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$order = $this->createOrder(self::$company->id, self::$lender->id, [
            'status' => FinancingOrderStatus::MurabahaSaleCompleted,
        ]);

        self::$notification = new Fluent([
            'notificationHeaderAndEntity' => new Fluent([
                'notificationId' => 'b6696016-6d7d-436a-9e69-ca204df33dc1',
                'notification' => 'Action Required for Promise to Purchase',
                'notificationEntityDetails' => new Fluent([
                    'notificationEntity' => [
                        new Fluent(['entityValue' => 148]),
                    ],
                ]),
            ]),
        ]);

        self::$ttiId = self::$notification->notificationHeaderAndEntity->notificationEntityDetails->notificationEntity[0]->entityValue;

        self::$traderOrder = TraderOrder::create([
            'status' => TraderOrderStatus::InProgress,
            'financing_order_id' => self::$order->id,
            'reference' => self::$ttiId,
            'provider' => 'fake',
        ]);
    }

    public function test_process_unprocessed_dmcc_notification_job_will_processed_only_if_the_active_trader_has_dmcc_or_fake_as_provider()
    {
        $this->expectException(TraderNotSupported::class);
        config()->set('trader.default', 'wrong provider');
        Bus::fake();

        (new ProcessUnprocessedDmccNotification(self::$notification))->handle();
    }

    public function test_process_unprocessed_dmcc_notification_job_will_processed_only_if_the_current_order_status_is_murabaha_sale_completed()
    {
        Bus::fake();
        $statuses = FinancingOrderStatus::getValues();
        FinancingOrder::unsetEventDispatcher();
        foreach ($statuses as  $status) {
            FinancingOrder::first()
                ->update(['status' => $status]);
            self::$order->refresh();

            (new ProcessUnprocessedDmccNotification(self::$notification))->handle();
            self::$traderOrder->refresh();

            self::$traderOrder->update(['status' => TraderOrderStatus::InProgress]);
            $this->assertFalse(self::$traderOrder->status->is(TraderOrderStatus::Completed));
        }
    }

    public function test_process_unprocessed_dmcc_notification_process_notification_is_called_successfully()
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
