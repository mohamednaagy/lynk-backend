<?php

namespace Tests\Unit\Jobs\General;

use App\Enums\DmccMurabhaStep;
use App\Enums\FinancingOrderHistory;
use App\Enums\Role;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\User;
use App\Support\Traders\Drivers\Dmcc\Jobs\V1\ProcessDmccMpoSaleCompleteNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\Support\FinancingOrders\TraderOrderScenario;
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
        self::$order = OrderScenario::inProgress()
            ->lender(self::$company)
            ->creator(self::$lender)
            ->commit()
            ->model();

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

        /** @var TraderOrder $traderOrderDmcc */
        $traderOrderDmcc = InProgressOrder::of(self::$order)->createTraderOrder('dmcc', 1);

        TraderOrderScenario::of($traderOrderDmcc)
            ->reset()
            ->moveToStep(DmccMurabhaStep::MurabhaOfferIssued);

        (new ProcessDmccMpoSaleCompleteNotification(self::$notification))->handle();

        $this->assertTrue($traderOrderDmcc->doesLastActionMatchWith(FinancingOrderHistory::MurabahaSaleCompleted));
    }

    public function test_process_dmcc_mpo_sale_complete_notification_fake_driver_success()
    {
        config()->set('trader.default', 'fake');

        /** @var TraderOrder $traderOrderFake */
        $traderOrderFake = InProgressOrder::of(self::$order)->createTraderOrder('fake', 1);

        TraderOrderScenario::of($traderOrderFake)
            ->reset()
            ->moveToStep(DmccMurabhaStep::MurabhaOfferIssued);

        (new ProcessDmccMpoSaleCompleteNotification(self::$notification))->handle();

        self::$order = self::$order->fresh();

        $this->assertTrue($traderOrderFake->doesLastActionMatchWith(FinancingOrderHistory::MurabahaSaleCompleted));
    }

    public function test_process_dmcc_mpo_sale_complete_notification_with_invalid_status()
    {
        config()->set('trader.default', 'fake');

        /** @var TraderOrder $traderOrderFake */
        $traderOrderFake = InProgressOrder::of(self::$order)->createTraderOrder('fake', 1);

        $financeHistories = FinancingOrderHistory::asArray();

        foreach ($financeHistories as $financeHistory) {
            if (in_array($financeHistory, [
                FinancingOrderHistory::AttachMpoDocument,
                FinancingOrderHistory::GetTtiId,
                FinancingOrderHistory::OrderCancelled,
                FinancingOrderHistory::Expired,
            ])) {
                continue;
            }

            TraderOrderScenario::of($traderOrderFake)
                ->reset()
                ->moveToHistory($financeHistory);

            (new ProcessDmccMpoSaleCompleteNotification(self::$notification))->handle();

            $this->assertTrue($traderOrderFake->doesLastActionMatchWith($financeHistory));
        }
    }
}
