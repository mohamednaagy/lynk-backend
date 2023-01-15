<?php

namespace Tests\Unit\Jobs\General;

use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Jobs\Dmcc\ProcessAskClientForWakala;
use App\Jobs\Dmcc\ProcessDmccContractSignedOrder;
use App\Jobs\Dmcc\ProcessDmccRespondedToPtpOrder;
use App\Jobs\Dmcc\ProcessPtpDocumentRetrievedOrder;
use App\Jobs\General\ProcessFinancingOrders;
use App\Jobs\General\ProcessInProgressOrder;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class ProcessFinancingOrdersTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    public Company $company;

    public User $lender;

    public function setUp(): void
    {
        parent::setUp();

        [$this->company] = $this->createCompany();

        $this->lender = $this->createLenderUser($this->company->id, Role::LenderAdmin);
    }

    public function test_process_financing_orders_approved_orders_matching_process_in_progress_order_job()
    {
        $this->createOrder($this->company->id, $this->lender->id, ['status' => FinancingOrderStatus::Approved]);

        Bus::fake();

        (new ProcessFinancingOrders)->handle();

        Bus::assertDispatched(ProcessInProgressOrder::class);
    }

    public function test_process_financing_orders_responded_to_ptp_status_matching_process_dmcc_responded_to_ptp_order_job()
    {
        $this->createOrder($this->company->id, $this->lender->id, ['status' => FinancingOrderStatus::RespondedToPtp]);

        Bus::fake();

        (new ProcessFinancingOrders)->handle();

        Bus::assertDispatched(ProcessDmccRespondedToPtpOrder::class);
    }

    public function test_process_financing_orders_ptp_document_retrieved_status_matching_process_ptp_document_retrieved_order_job()
    {
        $this->createOrder($this->company->id, $this->lender->id, ['status' => FinancingOrderStatus::PtpDocumentRetrieved]);

        Bus::fake();

        (new ProcessFinancingOrders)->handle();

        Bus::assertDispatched(ProcessPtpDocumentRetrievedOrder::class);
    }

    public function test_process_financing_orders_responded_contract_signed_status_matching_process_dmcc_contract_signed_order_job()
    {
        $this->createOrder($this->company->id, $this->lender->id, ['status' => FinancingOrderStatus::ContractSigned]);

        Bus::fake();

        (new ProcessFinancingOrders)->handle();

        Bus::assertDispatched(ProcessDmccContractSignedOrder::class);
    }

    public function test_process_financing_orders_commodity_sold_to_customer_status_matching_process_ask_client_for_wakala_job()
    {
        $this->createOrder($this->company->id, $this->lender->id, ['status' => FinancingOrderStatus::CommoditySoldToCustomer]);

        Bus::fake();

        (new ProcessFinancingOrders)->handle();

        Bus::assertDispatched(ProcessAskClientForWakala::class);
    }

    public function test_process_financing_orders_client_wakala_completed_status_not_matching_any_job()
    {
        $this->createOrder($this->company->id, $this->lender->id, ['status' => FinancingOrderStatus::ClientWakalaCompleted]);

        Bus::fake();

        (new ProcessFinancingOrders)->handle();

        Bus::assertNothingDispatched();
    }
}
