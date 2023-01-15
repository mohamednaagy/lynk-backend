<?php

namespace Jobs\General;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\TraderOrderStatus;
use App\Jobs\Dmcc\ProcessDmccMpoSaleCompleteNotification;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class ProcessDmccMpoSaleCompleteNotificationTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    protected static Company $company;

    protected static User $lender;

    protected static FinancingOrder $order;

    protected static TraderOrder|Model $traderOrderDmcc;

    protected static TraderOrder|Model $traderOrderFake;

    public function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany();
        self::$lender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$order = $this->createOrder(self::$company->id, self::$lender->id, [
            'status' => FinancingOrderStatus::MurabhaOfferIssued,
        ]);

        self::$traderOrderDmcc = self::$order->traderOrders()->create([
            'provider' => 'dmcc',
            'reference' => '1',
            'status' => TraderOrderStatus::InProgress,
        ]);
        self::$traderOrderFake = self::$order->traderOrders()->create([
            'provider' => 'fake',
            'reference' => '1',
            'status' => TraderOrderStatus::InProgress,
        ]);
    }

    public function test_process_dmcc_mpo_sale_complete_notification_dmcc_driver_success()
    {
        config()->set('trader.default', 'dmcc');

        $ttiId = 1;

        $notification = (object) [
            'notificationHeaderAndEntity' => (object) [
                'notificationEntityDetails' => (object) [
                    'notificationEntity' => [(object) ['entityValue' => $ttiId]],
                ],
            ],
        ];

        (new ProcessDmccMpoSaleCompleteNotification($notification))->handle();

        self::$order = self::$order->fresh();

        $traderOrder = self::$order->traderOrders()
            ->where('reference', $ttiId)
            ->where('status', TraderOrderStatus::InProgress)
            ->where('provider', 'dmcc')
            ->first();
        $traderOrderHistory = $traderOrder->traderHistories()->first();

        $this->assertTrue(self::$order->status->is(FinancingOrderStatus::MurabahaSaleCompleted));
        $this->assertEquals($traderOrderHistory->action, FinancingOrderHistory::MurabahaSaleCompleted);
    }

    public function test_process_dmcc_mpo_sale_complete_notification_fake_driver_success()
    {
        config()->set('trader.default', 'fake');

        $ttiId = 1;

        $notification = (object) [
            'notificationHeaderAndEntity' => (object) [
                'notificationEntityDetails' => (object) [
                    'notificationEntity' => [(object) ['entityValue' => $ttiId]],
                ],
            ],
        ];

        (new ProcessDmccMpoSaleCompleteNotification($notification))->handle();

        self::$order = self::$order->fresh();

        $traderOrder = self::$order->traderOrders()
            ->where('reference', $ttiId)
            ->where('status', TraderOrderStatus::InProgress)
            ->where('provider', 'dmcc')
            ->first();
        $traderOrderHistory = $traderOrder->traderHistories()->first();

        $this->assertTrue(self::$order->status->is(FinancingOrderStatus::MurabahaSaleCompleted));
        $this->assertEquals(FinancingOrderHistory::MurabahaSaleCompleted, $traderOrderHistory->action);
    }
}
