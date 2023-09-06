<?php

namespace Tests\Unit\Jobs\General;

use App\Enums\FinancingOrderHistory;
use App\Enums\Role;
use App\Jobs\General\ProcessAskClientForWakala;
use App\Jobs\General\ProcessFinancingOrders;
use App\Jobs\General\ProcessInProgressOrder;
use App\Models\Company;
use App\Models\TraderOrder;
use App\Models\User;
use App\Support\Traders\Drivers\Dmcc\Jobs\V1\ProcessDmccMpoOrder;
use App\Support\Traders\Drivers\Dmcc\Jobs\V1\ProcessDmccRespondedToPtpOrder;
use App\Support\Traders\Drivers\Dmcc\Jobs\V1\ProcessDmccSellingCommodityToCustomerOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\Support\FinancingOrders\TraderOrderScenario;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class ProcessFinancingOrdersTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    public static Company $company;

    public static User $lender;

    public function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany();

        self::$lender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
    }

    public function test_process_financing_orders_approved_orders_matching_process_in_progress_order_job()
    {
        OrderScenario::approved(self::$lender, now())
            ->lender(self::$company)
            ->creator(self::$lender)
            ->commit();

        Bus::fake();

        (new ProcessFinancingOrders)->handle();

        Bus::assertDispatched(ProcessInProgressOrder::class);
    }

    public function test_process_financing_orders_responded_to_ptp_history_matching_process_dmcc_responded_to_ptp_order_job()
    {
        $financingOrder = OrderScenario::inProgress()
            ->lender(self::$company)
            ->creator(self::$lender)
            ->commit();

        /** @var TraderOrder $traderOrder */
        $traderOrder = InProgressOrder::of($financingOrder)->createTraderOrder();

        TraderOrderScenario::of($traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::RespondPtp);

        Bus::fake();

        (new ProcessFinancingOrders)->handle();

        Bus::assertDispatched(ProcessDmccRespondedToPtpOrder::class);
    }

    public function test_process_financing_orders_ptp_document_retrieved_history_not_matching_any_job()
    {
        $financingOrder = OrderScenario::inProgress()
            ->lender(self::$company)
            ->creator(self::$lender)
            ->commit();

        $traderOrder = InProgressOrder::of($financingOrder)->createTraderOrder();

        TraderOrderScenario::of($traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::GetPtpDocument);

        Bus::fake();

        (new ProcessFinancingOrders)->handle();

        Bus::assertNothingDispatched();
    }

    public function test_process_financing_orders_responded_contract_signed_history_matching_process_dmcc_contract_signed_order_job()
    {
        $financingOrder = OrderScenario::inProgress()
            ->lender(self::$company)
            ->creator(self::$lender)
            ->commit();

        $traderOrder = InProgressOrder::of($financingOrder)->createTraderOrder();

        TraderOrderScenario::of($traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::ContractSigned);

        Bus::fake();

        (new ProcessFinancingOrders)->handle();

        Bus::assertDispatched(ProcessAskClientForWakala::class);
    }

    public function test_process_financing_orders_commodity_sold_to_customer_history_matching_process_ask_client_for_wakala_job()
    {
        $financingOrder = OrderScenario::inProgress()
            ->lender(self::$company)
            ->creator(self::$lender)
            ->commit();

        $traderOrder = InProgressOrder::of($financingOrder)->createTraderOrder();

        TraderOrderScenario::of($traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::CreateSellingCommodityToCustomerDocument);

        Bus::fake();

        (new ProcessFinancingOrders)->handle();

        Bus::assertDispatched(ProcessDmccMpoOrder::class);
    }

    public function test_process_financing_orders_client_wakala_completed_history_matching_process_dmcc_client_wakala_completed_order_job()
    {
        $financingOrder = OrderScenario::inProgress()
            ->lender(self::$company)
            ->creator(self::$lender)
            ->commit();

        $traderOrder = InProgressOrder::of($financingOrder)->createTraderOrder();

        TraderOrderScenario::of($traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::ClientWakalaAccepted);

        Bus::fake();

        (new ProcessFinancingOrders)->handle();

        Bus::assertDispatched(ProcessDmccSellingCommodityToCustomerOrder::class);
    }
}
