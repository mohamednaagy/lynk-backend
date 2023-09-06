<?php

namespace Tests\Unit\Jobs\Dmcc;

use App\Enums\DmccMurabhaStep;
use App\Enums\Role;
use App\Exceptions\TraderNotSupportedException;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\User;
use App\Support\Traders\Drivers\Dmcc\Jobs\V1\ProcessUnprocessedDmccNotification;
use App\Support\Traders\Events\ProcessNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Fluent;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\Support\FinancingOrders\TraderOrderScenario;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class ProcessUnprocessedDmccNotificationTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    protected static Company $company;

    protected static User $lender;

    protected static Fluent $notification;

    protected static Model|TraderOrder $traderOrder;

    protected static $ttiId;

    protected static FinancingOrder $order;

    protected function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany();
        self::$lender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$order = OrderScenario::inProgress()
            ->lender(self::$company)
            ->creator(self::$lender)
            ->commit()
            ->model();

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

        self::$traderOrder = InProgressOrder::of(self::$order)->createTraderOrder('fake', self::$ttiId);

        TraderOrderScenario::of(self::$traderOrder)
            ->moveToStep(DmccMurabhaStep::MurabahaSaleCompleted);
    }

    public function test_process_unprocessed_dmcc_notification_job_will_processed_only_if_the_active_trader_has_dmcc_as_provider()
    {
        config()->set('trader.default', 'dmcc');
        Bus::fake();
        Event::fake([
            ProcessNotification::class,
        ]);

        (new ProcessUnprocessedDmccNotification(self::$notification))->handle();

        Event::assertDispatched(ProcessNotification::class);
    }

    public function test_process_unprocessed_dmcc_notification_job_will_processed_only_if_the_active_trader_has_fake_as_provider()
    {
        config()->set('trader.default', 'fake');
        Bus::fake();
        Event::fake([
            ProcessNotification::class,
        ]);

        (new ProcessUnprocessedDmccNotification(self::$notification))->handle();

        Event::assertDispatched(ProcessNotification::class);
    }

    public function test_process_unprocessed_dmcc_notification_process_notification_with_wrong_driver_will_fail()
    {
        $this->expectException(TraderNotSupportedException::class);
        config()->set('trader.default', 'wrong driver');

        (new ProcessUnprocessedDmccNotification(self::$notification))->handle();
    }
}
