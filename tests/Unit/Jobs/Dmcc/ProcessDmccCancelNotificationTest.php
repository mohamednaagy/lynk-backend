<?php

namespace Tests\Unit\Jobs\Dmcc;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\TraderOrder;
use App\Models\User;
use App\Support\Traders\Drivers\Dmcc\Jobs\V1\ProcessDmccCancelNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FinancingOrders\CommittedOrder;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;
use Throwable;

class ProcessDmccCancelNotificationTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    protected static Company $company;

    protected static User $userLender;

    protected static CommittedOrder $financingOrder;

    protected static mixed $notification;

    protected static Model|TraderOrder $traderOrder;

    protected function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createLenderCompany('2000', ['company_cr' => '1234567891']);
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin, ['email' => 'lenderAdmin@bim.com']);

        self::$financingOrder = OrderScenario::inProgress()
            ->lender(self::$company)
            ->creator(self::$userLender)
            ->requireVerification(true)
            ->commit();

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
        $ttiId = self::$notification
            ->notificationHeaderAndEntity
            ->notificationEntityDetails
            ->notificationEntity[0]
            ->entityValue;

        // create trader order
        self::$traderOrder = InProgressOrder::of(self::$financingOrder)->createTraderOrder('fake', $ttiId);
        self::$financingOrder->status(FinancingOrderStatus::PendingCancellation)->commit();
    }

    /**
     * @throws Throwable
     */
    public function test_process_cannot_proceed_with_invalid_trader_order_provider()
    {
        //change the trader order provider with invalid one
        self::$traderOrder->update(['provider' => 'invalid']);

        $process = new ProcessDmccCancelNotification(self::$notification);
        $process->handle();

        $this->assertFalse(
            self::$financingOrder->model()->fresh()->status->is(FinancingOrderStatus::Cancelled)
        );
    }

    /**
     * @throws Throwable
     */
    public function test_process_cannot_proceed_when_order_status_not_pending_cancellation()
    {
        foreach (FinancingOrderStatus::getValues() as $status) {
            if (
                $status == FinancingOrderStatus::PendingCancellation ||
                $status == FinancingOrderStatus::Cancelled
            ) {
                continue;
            }

            //change the order status with invalid one
            self::$financingOrder->model()->update(['status' => $status]);

            $process = new ProcessDmccCancelNotification(self::$notification);
            $process->handle();

            $this->assertFalse(
                self::$financingOrder->model()->fresh()->status->is(FinancingOrderStatus::Cancelled)
            );
        }
    }

    /**
     * @throws Throwable
     */
    public function test_process_creates_trader_order_history_with_order_cancelled_status_succeed()
    {
        $process = new ProcessDmccCancelNotification(self::$notification);
        $process->handle();

        $this->assertEquals(
            FinancingOrderHistory::OrderCancelled,
            self::$traderOrder->traderHistories()->latest('id')->first()->action
        );
    }

    /**
     * @throws Throwable
     */
    public function test_process_changes_order_status_to_cancelled_succeed()
    {
        $process = new ProcessDmccCancelNotification(self::$notification);
        $process->handle();

        $this->assertTrue(
            self::$financingOrder->model()->fresh()->status->is(FinancingOrderStatus::Cancelled)
        );
    }

    /**
     * @throws Throwable
     */
    public function test_process_changes_trader_order_status_to_cancelled_succeed()
    {
        $process = new ProcessDmccCancelNotification(self::$notification);
        $process->handle();

        $this->assertEquals(
            TraderOrderStatus::Cancelled,
            self::$financingOrder->model()->traderOrders()->first()->status->value
        );
    }

    /**
     * @throws Throwable
     */
    public function test_process_passes_with_valid_provider_as_fake_succeed()
    {
        $process = new ProcessDmccCancelNotification(self::$notification);
        $process->handle();

        $this->assertTrue(
            self::$financingOrder->model()->fresh()->status->is(FinancingOrderStatus::Cancelled)
        );
    }

    /**
     * @throws Throwable
     */
    public function test_process_passes_with_valid_provider_as_dmcc_succeed()
    {
        self::$traderOrder->update(['provider' => 'dmcc']);

        $process = new ProcessDmccCancelNotification(self::$notification);
        $process->handle();

        $this->assertTrue(
            self::$financingOrder->model()->fresh()->status->is(FinancingOrderStatus::Cancelled)
        );
    }
}
