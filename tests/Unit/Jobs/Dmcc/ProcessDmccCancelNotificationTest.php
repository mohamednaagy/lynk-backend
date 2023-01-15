<?php

namespace Tests\Unit\Jobs\Dmcc;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\TraderOrderStatus;
use App\Jobs\Dmcc\ProcessDmccCancelNotification;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\User;
use App\Support\Traders\Facades\Trader;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;
use Throwable;

class ProcessDmccCancelNotificationTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    protected static Company $company;

    protected static User $userLender;

    protected static FinancingOrder $financingOrder;

    protected static $notification;

    protected static Model|TraderOrder $traderOrder;

    protected function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany('2000', ['company_cr' => '1234567891']);
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'lenderAdmin@bim.com');
        self::$financingOrder = $this->createOrder(
            self::$company->id,
            self::$userLender->id,
            [
                'is_verification_required' => true,
                'status' => FinancingOrderStatus::PendingCancellation,
            ]
        );

        $trader = Trader::driver('fake');

        $notifications = collect($trader->fetchNotifications('FYI'));
        self::$notification = $notifications->first() ?? (object) [
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
        $ttiId = self::$notification->
        notificationHeaderAndEntity->
        notificationEntityDetails->
        notificationEntity[0]->entityValue;

        // create trader order
        self::$traderOrder = self::$financingOrder->traderOrders()->create([
            'provider' => 'fake',
            'reference' => $ttiId,
            'status' => TraderOrderStatus::InProgress,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function test_process_cannot_proceed_with_invalid_trader_order_provider_failed()
    {
        //change the trader order provider with invalid one
        self::$financingOrder->traderOrders()->first()->update(['provider' => 'invalid']);

        $process = new ProcessDmccCancelNotification(self::$notification);
        $process->handle();

        $this->assertNotEquals(
            FinancingOrderStatus::Cancelled,
            self::$financingOrder->fresh()->status->value
        );
    }

    /**
     * @throws Throwable
     */
    public function test_process_cannot_proceed_when_order_status_not_PendingCancellation_failed()
    {
        //change the order status with invalid one
        self::$financingOrder->update(['status' => FinancingOrderStatus::PendingApproval]);

        $process = new ProcessDmccCancelNotification(self::$notification);
        $process->handle();

        $this->assertNotEquals(
            FinancingOrderStatus::Cancelled,
            self::$financingOrder->fresh()->status->value
        );
    }

    /**
     * @throws Throwable
     */
    public function test_process_creates_trader_order_history_with_OrderCancelled_status_succeed()
    {
        $process = new ProcessDmccCancelNotification(self::$notification);
        $process->handle();

        $this->assertEquals(
            FinancingOrderHistory::OrderCancelled,
            self::$traderOrder->traderHistories()->first()->action
        );
    }

    /**
     * @throws Throwable
     */
    public function test_process_changes_order_status_to_Cancelled_succeed()
    {
        $process = new ProcessDmccCancelNotification(self::$notification);
        $process->handle();

        $this->assertEquals(
            FinancingOrderStatus::Cancelled,
            self::$financingOrder->fresh()->status->value
        );
    }

    /**
     * @throws Throwable
     */
    public function test_process_changes_trader_order_status_to_Cancelled_succeed()
    {
        $process = new ProcessDmccCancelNotification(self::$notification);
        $process->handle();

        $this->assertEquals(
            TraderOrderStatus::Cancelled,
            self::$financingOrder->traderOrders()->first()->status
        );
    }
}
