<?php

namespace Tests\Unit\Jobs\Dmcc;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\Role;
use App\Enums\TraderOrderStatus;
use App\Jobs\Dmcc\ProcessPtpDocumentRetrievedOrder;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\Media;
use App\Models\TraderOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;
use Throwable;

class ProcessPtpDocumentRetrievedOrderTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    protected static Company $company;

    protected static User $userLender;

    protected static FinancingOrder $financingOrder;

    protected static Model|TraderOrder $traderOrder;

    protected function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createLenderCompany('2000', ['company_cr' => '1234567891']);
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin, ['email' => 'lenderAdmin@bim.com']);
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
    public function test_job_cannot_proceed_with_invalid_trader_order_provider()
    {
        //change the trader order provider with invalid one
        self::$traderOrder->update(['provider' => 'invalid']);

        $process = new ProcessPtpDocumentRetrievedOrder(self::$financingOrder->id);
        $process->handle();

        $this->assertFalse(
            self::$financingOrder->fresh()->status->is(FinancingOrderStatus::CommodityPurchased)
        );
    }

    /**
     * @throws Throwable
     */
    public function test_job_cannot_proceed_when_order_status_not_ptp_document_retrieved()
    {
        foreach (FinancingOrderStatus::getValues() as $status) {
            if (
                $status == FinancingOrderStatus::PtpDocumentRetrieved ||
                $status == FinancingOrderStatus::CommodityPurchased
            ) {
                continue;
            }

            //change the order status with invalid one
            self::$financingOrder->update(['status' => FinancingOrderStatus::PendingApproval]);

            $process = new ProcessPtpDocumentRetrievedOrder(self::$financingOrder->id);
            $process->handle();

            $this->assertFalse(
                self::$financingOrder->fresh()->status->is(FinancingOrderStatus::CommodityPurchased)
            );
        }
    }

    /**
     * @throws Throwable
     */
    public function test_job_creates_ownership_document_succeed()
    {
        Storage::fake();
        UploadedFile::fake();

        $process = new ProcessPtpDocumentRetrievedOrder(self::$financingOrder->id);
        $process->handle();

        $this->assertDatabaseHas((new Media())->getTable(), [
            'model_id' => self::$traderOrder->id,
            'model_type' => (new TraderOrder)->getMorphClass(),
            'collection_name' => TraderOrderMediaCollection::TransferOwnershipToLender,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function test_job_creates_trader_order_history_with_correct_status_succeed()
    {
        $process = new ProcessPtpDocumentRetrievedOrder(self::$financingOrder->id);
        $process->handle();

        $this->assertEquals(
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
            self::$traderOrder->traderHistories()->first()->action
        );
    }

    /**
     * @throws Throwable
     */
    public function test_job_changes_order_status_to_CommodityPurchased_succeed()
    {
        $process = new ProcessPtpDocumentRetrievedOrder(self::$financingOrder->id);
        $process->handle();

        $this->assertTrue(
            self::$financingOrder->fresh()->status->is(FinancingOrderStatus::CommodityPurchased)
        );
    }
}
