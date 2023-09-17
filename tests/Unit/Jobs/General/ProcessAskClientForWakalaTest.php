<?php

namespace Tests\Unit\Jobs\General;

use App\Enums\FinancingOrderHistory;
use App\Enums\MurabhaStep;
use App\Enums\Trader;
use App\Jobs\General\ProcessAskClientForWakala;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\User;
use App\Support\Sms\Events\SmsSent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Support\FinancingOrders\CommittedOrder;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\Support\FinancingOrders\TraderOrderScenario;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class ProcessAskClientForWakalaTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany;

    protected static Company $company;

    protected static User $lender;

    protected static FinancingOrder $order;

    protected static Model|TraderOrder $traderOrder;

    protected function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany();
        self::$lender = $this->createLenderUser(self::$company->id);

        self::$order = OrderScenario::inProgress()
            ->requireVerification(true)
            ->lender(self::$company)
            ->creator(self::$lender)
            ->commit()
            ->model();

        self::$traderOrder = InProgressOrder::of(self::$order)->createTraderOrder();

        TraderOrderScenario::of(self::$traderOrder)
            ->moveToStep(MurabhaStep::ContractSigned);
    }

    public function test_process_ask_client_for_wakala_processed_if_murabha_step_contract_signed()
    {
        $processOrder = new ProcessAskClientForWakala(self::$traderOrder->id);

        $processOrder->handle();

        $this->assertTrue(self::$traderOrder->checkOrderHistoryAction(FinancingOrderHistory::WaitingClientWakala));
    }

    public function test_process_ask_client_for_wakala_will_not_processed_if_murabha_step_not_contract_signed()
    {
        $murabhaSteps = MurabhaStep::getValues();
        foreach ($murabhaSteps as $murabhaStep) {
            if (
                $murabhaStep == MurabhaStep::ContractSigned
                || $murabhaStep == MurabhaStep::TraderOrderCreated
                || ((in_array(self::$traderOrder->provider, [Trader::Dmcc, Trader::FakeDmcc]) && $murabhaStep == MurabhaStep::TransferOwnershipToLender))
            ) {
                continue;
            }

            $traderOrder = TraderOrderScenario::of(self::$traderOrder)
                ->reset()
                ->moveToStep($murabhaStep)
                ->getTraderOrder();

            $processOrder = new ProcessAskClientForWakala($traderOrder->id);
            $processOrder->handle();

            $this->assertTrue($traderOrder->checkOrderStepComplete($murabhaStep));
        }
    }

    public function test_process_ask_client_for_wakala_will_not_processed_if_order_is_verification_required_false()
    {
        CommittedOrder::of(self::$order)->requireVerification(false)->commit();

        $processOrder = new ProcessAskClientForWakala(self::$traderOrder->id);

        $processOrder->handle();

        $this->assertTrue(self::$traderOrder->checkOrderHistoryAction(FinancingOrderHistory::WaitingClientWakala));
    }

    public function test_process_ask_client_for_wakala_sms_sent_successfully()
    {
        Event::fake([
            SmsSent::class,
        ]);

        $processOrder = new ProcessAskClientForWakala(self::$traderOrder->id);

        $processOrder->handle();

        Event::assertDispatched(SmsSent::class);
    }
}
