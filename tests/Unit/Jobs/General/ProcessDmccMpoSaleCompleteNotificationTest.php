<?php

namespace Tests\Unit\Jobs\General;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\TraderOrderStatus;
use App\Jobs\Dmcc\ProcessDmccMpoSaleCompleteNotification;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class ProcessDmccMpoSaleCompleteNotificationTest extends TestCase
{
    use RefreshDatabase, InteractsWithCompany, InteractsWithUser;

    protected static Company $company;

    protected static User $lender;

    protected static FinancingOrder $order;

    protected static mixed $notification;

    public function setUp(): void
    {
        parent::setUp();

        self::$company = $this->createCompanyWithoutWallet();
        self::$lender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$order = $this->createOrder(self::$company->id, self::$lender->id, [
            'status' => FinancingOrderStatus::MurabhaOfferIssued,
        ]);

        $ttiId = 1;

        self::$notification = (object) [
            'notificationHeaderAndEntity' => (object) [
                'notificationEntityDetails' => (object) [
                    'notificationEntity' => [
                        0 => (object) [
                            'entityValue' => $ttiId,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function test_process_dmcc_mpo_sale_complete_notification_dmcc_driver_success()
    {
        config()->set('trader.default', 'dmcc');

        $traderOrderDmcc = self::$order->traderOrders()->create([
            'provider' => 'dmcc',
            'reference' => '1',
            'status' => TraderOrderStatus::InProgress,
        ]);

        (new ProcessDmccMpoSaleCompleteNotification(self::$notification))->handle();

        self::$order = self::$order->fresh();

        $traderOrderHistory = $traderOrderDmcc->traderHistories()->where('action', FinancingOrderHistory::MurabahaSaleCompleted)->exists();

        $this->assertTrue(self::$order->status->is(FinancingOrderStatus::MurabahaSaleCompleted));
        $this->assertTrue($traderOrderHistory);
    }

    public function test_process_dmcc_mpo_sale_complete_notification_fake_driver_success()
    {
        config()->set('trader.default', 'fake');

        $traderOrderFake = self::$order->traderOrders()->create([
            'provider' => 'fake',
            'reference' => '1',
            'status' => TraderOrderStatus::InProgress,
        ]);

        (new ProcessDmccMpoSaleCompleteNotification(self::$notification))->handle();

        self::$order = self::$order->fresh();

        $traderOrderHistory = $traderOrderFake->traderHistories()->where('action', FinancingOrderHistory::MurabahaSaleCompleted)->exists();

        $this->assertTrue(self::$order->status->is(FinancingOrderStatus::MurabahaSaleCompleted));
        $this->assertTrue($traderOrderHistory);
    }

    public function test_process_dmcc_mpo_sale_complete_notification_with_invalid_status()
    {
        self::$order->traderOrders()->create([
            'provider' => 'fake',
            'reference' => '1',
            'status' => TraderOrderStatus::InProgress,
        ]);

        collect(FinancingOrderStatus::asSelectArray())
            ->except([FinancingOrderStatus::MurabhaOfferIssued])
            ->keys()
            ->each(function ($status) {
                self::$order->update(['status' => $status]);

                (new ProcessDmccMpoSaleCompleteNotification(self::$notification))->handle();

                self::$order = self::$order->fresh();

                $this->assertTrue(self::$order->status->is($status));
            });
    }
}
