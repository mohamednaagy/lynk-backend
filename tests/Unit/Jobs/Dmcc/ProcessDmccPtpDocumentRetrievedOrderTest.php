<?php

namespace Tests\Unit\Jobs\Dmcc;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\Role;
use App\Jobs\Dmcc\ProcessDmccPtpDocumentRetrievedOrder;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\User;
use CodeDredd\Soap\Facades\Soap;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\Support\FinancingOrders\TraderOrderScenario;
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

        self::$order = OrderScenario::inProgress()
            ->requireVerification(true)
            ->lender(self::$company)
            ->creator(self::$lender)
            ->commit()
            ->model();

        self::$traderOrder = InProgressOrder::of(self::$order)->createTraderOrder();

        TraderOrderScenario::of(self::$traderOrder)->moveToHistory(FinancingOrderHistory::AttachTtiHoldingCertificateDocument);

        self::$notification = (object) [
            'notificationHeaderAndEntity' => (object) [
                'notification' => 'Action Required for Issue Murabaha Purchase Offer',
                'notificationEntityDetails' => (object) [
                    'notificationEntity' => [
                        (object) [
                            'entityValue' => self::$traderOrder->reference,
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

        $this->assertTrue(
            self::$traderOrder->checkOrderHistoryAction(FinancingOrderHistory::CreateTransferOwnershipToLenderDocument)
        );

        $this->assertTrue(self::$traderOrder->hasMedia(TraderOrderMediaCollection::TransferOwnershipToLender));
    }

    public function test_process_dmcc_ptp_document_retrieved_with_fake_as_trader_order_will_success()
    {
        self::$traderOrder->update([
            'provider' => 'fake',
        ]);

        (new ProcessDmccPtpDocumentRetrievedOrder(self::$notification))->handle();

        $this->assertTrue(
            self::$traderOrder->checkOrderHistoryAction(FinancingOrderHistory::CreateTransferOwnershipToLenderDocument)
        );

        $this->assertTrue(self::$traderOrder->hasMedia(TraderOrderMediaCollection::TransferOwnershipToLender));
    }

    public function test_process_dmcc_ptp_document_retrieved_with_not_supported_trader_will_fail()
    {
        self::$traderOrder->update([
            'provider' => 'else',
        ]);

        (new ProcessDmccPtpDocumentRetrievedOrder(self::$notification))->handle();

        $this->assertFalse(
            self::$traderOrder->checkOrderHistoryAction(FinancingOrderHistory::CreateTransferOwnershipToLenderDocument)
        );

        $this->assertFalse(self::$traderOrder->hasMedia(TraderOrderMediaCollection::TransferOwnershipToLender));
    }

    public function test_process_dmcc_ptp_document_retrieved_when_muraha_step_not_ptp_document_retrieved_fail()
    {
        $financeHistories = FinancingOrderHistory::getValues();

        foreach ($financeHistories as $financeHistory) {
            if (in_array($financeHistory, [
                FinancingOrderHistory::AttachTtiHoldingCertificateDocument,
                FinancingOrderHistory::GetTtiId,
                FinancingOrderHistory::OrderCancelled,
                FinancingOrderHistory::Expired,
            ])) {
                continue;
            }

            TraderOrderScenario::of(self::$traderOrder)
                ->reset()
                ->moveToHistory($financeHistory);

            (new ProcessDmccPtpDocumentRetrievedOrder(self::$notification))->handle();

            $this->assertTrue(
                self::$traderOrder->doesLastActionMatchWith($financeHistory)
            );

            $this->assertFalse(self::$traderOrder->hasMedia(TraderOrderMediaCollection::TransferOwnershipToLender));
        }
    }

    public function test_process_ask_client_for_wakala_client_wakala_is_generated_successfully()
    {
        $processOrder = new ProcessDmccPtpDocumentRetrievedOrder(self::$notification);

        $processOrder->handle();

        $this->assertTrue(self::$traderOrder->hasMedia(TraderOrderMediaCollection::ClientWakala));
    }
}
