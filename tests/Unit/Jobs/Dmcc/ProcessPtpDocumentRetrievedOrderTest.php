<?php

namespace Tests\Unit\Jobs\Dmcc;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\TraderOrderStatus;
use App\Jobs\Dmcc\ProcessPtpDocumentRetrievedOrder;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;
use Throwable;

class ProcessPtpDocumentRetrievedOrderTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    protected static Company $company;

    protected static User $userLender;

    protected static FinancingOrder $financingOrder;

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
                'status' => FinancingOrderStatus::PtpDocumentRetrieved,
            ]
        );

        // create trader order
        self::$traderOrder = self::$financingOrder->traderOrders()->create([
            'provider' => 'fake',
            'reference' => '123456789',
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

        $process = new ProcessPtpDocumentRetrievedOrder(self::$financingOrder);
        $process->handle();

        $this->assertNotEquals(
            FinancingOrderStatus::CommodityPurchased,
            self::$financingOrder->fresh()->status->value
        );
    }

    /**
     * @throws Throwable
     */
    public function test_process_cannot_proceed_when_order_status_not_PtpDocumentRetrieved_failed()
    {
        //change the order status with invalid one
        self::$financingOrder->update(['status' => FinancingOrderStatus::PendingApproval]);

        $process = new ProcessPtpDocumentRetrievedOrder(self::$financingOrder);
        $process->handle();

        $this->assertNotEquals(
            FinancingOrderStatus::CommodityPurchased,
            self::$financingOrder->fresh()->status
        );
    }

    /**
     * @throws Throwable
     */
    public function test_process_fake_not_create_ownership_document_failed()
    {
        $process = new ProcessPtpDocumentRetrievedOrder(self::$financingOrder);
        $process->handle();

        $this->assertNull(
            self::$financingOrder->fresh()->getFirstMedia()
        );
    }

    /**
     * @throws Throwable
     */
    public function test_process_creates_trader_order_history_with_correct_status_succeed()
    {
        $process = new ProcessPtpDocumentRetrievedOrder(self::$financingOrder);
        $process->handle();

        $this->assertEquals(
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
            self::$traderOrder->traderHistories()->first()->action
        );
    }

    /**
     * @throws Throwable
     */
    public function test_process_changes_order_status_to_CommodityPurchased_succeed()
    {
        $process = new ProcessPtpDocumentRetrievedOrder(self::$financingOrder);
        $process->handle();

        $this->assertEquals(
            FinancingOrderStatus::CommodityPurchased,
            self::$financingOrder->fresh()->status->value
        );
    }
}
