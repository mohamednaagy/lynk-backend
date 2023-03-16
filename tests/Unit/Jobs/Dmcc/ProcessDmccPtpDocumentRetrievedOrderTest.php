<?php

namespace Tests\Unit\Jobs\Dmcc;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\Role;
use App\Enums\TraderOrderStatus;
use App\Jobs\Dmcc\ProcessDmccPtpDocumentRetrievedOrder;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\Media;
use App\Models\TraderOrder;
use App\Models\User;
use CodeDredd\Soap\Facades\Soap;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class ProcessDmccPtpDocumentRetrievedOrderTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    protected static Company $company;

    protected static User $lender;

    protected static FinancingOrder $order;

    protected static Model|TraderOrder $traderOrder;

    protected static object $notification;

    /**
     * @throws BindingResolutionException
     */
    protected function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany();
        self::$lender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$order = $this->createOrder(self::$company->id, self::$lender->id, [
            'is_verification_required' => true,
            'status' => FinancingOrderStatus::PtpDocumentRetrieved,
        ]);

        self::$traderOrder = self::$order->traderOrders()->create([
            'provider' => 'dmcc',
            'reference' => '123456789',
            'status' => TraderOrderStatus::InProgress,
        ]);

        self::$notification = (object) [
            'notificationHeaderAndEntity' => (object) [
                'notification' => 'Action Required for Issue Murabaha Purchase Offer',
                'notificationEntityDetails' => (object) [
                    'notificationEntity' => [
                        (object) [
                            'entityValue' => '123456789',
                        ],
                    ],
                ],
            ],
        ];

        Soap::fake(function () {
            return Soap::response([
                'successCode' => '0000',
                'errorCode' => '',
                'exchangeRate' => 'exchangeRate',
                'inventoryDetails' => [
                    [
                        'hsCodeDescription' => 'hsCodeDescription',
                        'quantity' => 10,
                        'totalValue' => 100,
                        'currency' => 'SAR',
                        'warehouseOrVaultId' => 'warehouseOrVaultId',
                        'owner' => 'owner',
                        'previousOwner' => 'previousOwner',
                        'newOwner' => 'newOwner',
                        'inventoryRecordId' => '12',
                        'warrantPercentage' => 'warrantPercentage',
                        'warehouseOrVaultOperatorId' => 'warehouseOrVaultOperatorId',
                        'warrantNo' => 'warrantNo',
                        'uom' => 'uom',
                        'hsCode' => 'hsCode',
                        'dateTimeOfPurchasingCommodity' => '01/01/2023 00:00 AM',
                        'warehouseOrVaultEmirates' => 'warehouseOrVaultEmirates',
                        'warehouseOrVaultCountry' => 'warehouseOrVaultCountry',
                    ],
                ],
            ], 200);
        });
    }

    public function test_process_dmcc_ptp_document_retrieved_with_dmcc_as_trader_will_success()
    {
        (new ProcessDmccPtpDocumentRetrievedOrder(self::$notification))->handle();

        $this->assertTrue(self::$order->fresh()->status->is(FinancingOrderStatus::CommodityPurchased));

        $this->assertEquals(
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
            self::$traderOrder->traderHistories()->first()->action
        );

        $this->assertDatabaseHas((new Media())->getTable(), [
            'model_id' => self::$traderOrder->id,
            'model_type' => (new TraderOrder)->getMorphClass(),
            'collection_name' => TraderOrderMediaCollection::TransferOwnershipToLender,
        ]);
    }

    public function test_process_dmcc_ptp_document_retrieved_with_fake_as_trader_order_will_success()
    {
        self::$traderOrder->update([
            'provider' => 'fake',
        ]);

        (new ProcessDmccPtpDocumentRetrievedOrder(self::$notification))->handle();

        $this->assertTrue(self::$order->fresh()->status->is(FinancingOrderStatus::CommodityPurchased));

        $this->assertEquals(
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
            self::$traderOrder->traderHistories()->first()->action
        );

        $this->assertDatabaseHas((new Media())->getTable(), [
            'model_id' => self::$traderOrder->id,
            'model_type' => (new TraderOrder)->getMorphClass(),
            'collection_name' => TraderOrderMediaCollection::TransferOwnershipToLender,
        ]);
    }

    public function test_process_dmcc_ptp_document_retrieved_with_not_supported_trader_will_fail()
    {
        self::$traderOrder->update([
            'provider' => 'else',
        ]);
        (new ProcessDmccPtpDocumentRetrievedOrder(self::$notification))->handle();
        $this->assertTrue(self::$order->fresh()->status->is(FinancingOrderStatus::PtpDocumentRetrieved));
    }

    public function test_process_dmcc_ptp_document_retrieved_when_order_status_not_ptp_document_retrieved_fail()
    {
        foreach (FinancingOrderStatus::getValues() as $status) {
            if (
                $status == FinancingOrderStatus::PtpDocumentRetrieved ||
                $status == FinancingOrderStatus::CommodityPurchased
            ) {
                continue;
            }
            //change the order status with invalid one
            self::$order->update(['status' => FinancingOrderStatus::PendingApproval]);
            (new ProcessDmccPtpDocumentRetrievedOrder(self::$notification))->handle();
            $this->assertTrue(self::$order->fresh()->status->isNot(FinancingOrderStatus::CommodityPurchased));
        }
    }
}
