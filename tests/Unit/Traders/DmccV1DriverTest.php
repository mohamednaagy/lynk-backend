<?php

namespace Tests\Unit\Traders;

use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\MurabhaStep;
use App\Exceptions\TraderException;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderHistory;
use App\Models\TraderOrder;
use App\Models\User;
use App\Support\Traders\Drivers\Dmcc\Strategies\DmccV1Driver;
use Carbon\Carbon;
use CodeDredd\Soap\Facades\Soap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\Support\FinancingOrders\TraderOrderScenario;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class DmccV1DriverTest extends TestCase
{
    use RefreshDatabase, InteractsWithCompany, InteractsWithUser;

    protected static Company $company;

    protected static User $lender;

    protected static FinancingOrder $order;

    protected static Model|TraderOrder $traderOrder;

    protected function setUp(): void
    {
        parent::setUp();

        self::$company = $this->createCompanyWithoutWallet();
        self::$lender = $this->createLenderUser(self::$company->id);

        self::$order = OrderScenario::inProgress()
            ->lender(self::$company)
            ->creator(self::$lender)
            ->commit()
            ->model();

        $data = [
            'products' => [
                [
                    'product' => 'Yogurt',
                    'quantity' => '10',
                    'amount' => '1000',
                    'currency' => 'SAR',
                    'warehouse' => 'Warehouse',
                    'owner' => 'Owner 1',
                    'previous_owner' => 'Owner 0',
                    'new_owner' => 'Owner 1',
                    'date_time_of_purchasing_commodity' => '2023-01-01 00:00:00',
                    'warehouse_or_vault_emirates' => 'Emirates',
                    'warehouse_or_vault_country' => 'Saudi Arabia',
                    'inventory_record_id' => '1000',
                    'warrant_percentage' => '100',
                    'warrant_no' => '658',
                    'hs_code' => '#234',
                    'uom' => 'Kilo',
                ],
                [
                    'product' => 'Yogurt 2',
                    'quantity' => '5',
                    'amount' => '500',
                    'currency' => 'SAR',
                    'warehouse' => 'Warehouse',
                    'owner' => 'Owner 1',
                    'previous_owner' => 'Owner 2',
                    'new_owner' => 'Owner 3',
                    'date_time_of_purchasing_commodity' => '2023-02-01 00:00:00',
                    'warehouse_or_vault_emirates' => 'Emirates',
                    'warehouse_or_vault_country' => 'Saudi Arabia',
                    'inventory_record_id' => '1000',
                    'warrant_percentage' => '100',
                    'warrant_no' => '658',
                    'hs_code' => '#234',
                    'uom' => 'Kilo',
                ],
            ],
        ];

        $data['exchange_rate'] = '3.75';

        self::$traderOrder = InProgressOrder::of(self::$order)->createTraderOrder(data: $data);
    }

    /**
     * @throws TraderException
     */
    public function test_create_trader_order_success(): void
    {
        $traderOrderCount = TraderOrder::query()->count();
        $traderOrderHistoryCount = TraderHistory::query()->count();

        Soap::fake(function () {
            return Soap::response([
                'ttiId' => '1',
                'errorCode' => '',
                'errorMessage' => '',
            ], 200);
        });

        (new DmccV1Driver())->createTraderOrder(self::$order);

        $this->assertDatabaseCount((new TraderOrder())->getTable(), $traderOrderCount + 1);
        $this->assertDatabaseCount((new TraderHistory())->getTable(), $traderOrderHistoryCount + 1);
    }

    /**
     * @throws TraderException
     */
    public function test_process_order_with_empty_string_fails(): void
    {
        $this->expectException(TraderException::class);

        $activityLogCount = Activity::query()->count();
        Soap::fake(function () {
            return Soap::response([
                'ttiId' => '',
                'errorCode' => '',
                'errorMessage' => 'error',
            ], 200);
        });

        (new DmccV1Driver())->createTraderOrder(self::$order);

        $this->assertDatabaseCount((new TraderOrder())->getTable(), 0);
        $this->assertDatabaseCount((new TraderHistory())->getTable(), 0);
        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    public function test_accept_agreement_fail(): void
    {

        $activityLogCount = Activity::query()->count();

        Soap::fake(function () {
            return Soap::response([
                'ttiId' => '',
                'errorCode' => '',
                'errorMessage' => 'error',
            ], 200);
        });

        $this->expectException(TraderException::class);

        (new DmccV1Driver())->acceptAgreement();

        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    /**
     * @throws TraderException
     */
    public function test_fetch_notifications_success(): void
    {
        Soap::fake(function () {
            return Soap::response([
                'NotificationAllDetailsResponse' => [
                    [
                        'notificationAllDetailsResponse' => [
                            'notificationDetails',
                        ],
                    ],
                ],
            ], 200);
        });

        $response = (new DmccV1Driver())->fetchNotifications('ACTIONABLE');

        $this->assertIsArray($response);
    }

    /**
     * @throws TraderException
     */
    public function test_fetch_notifications_with_invalid_response_fails(): void
    {
        $this->expectException(TraderException::class);

        $activityLogCount = Activity::query()->count();

        Soap::fake(function () {
            return Soap::response([
                'NotificationAllDetailsResponse' => [],
            ], 500);
        });

        (new DmccV1Driver())->fetchNotifications('ACTIONABLE');

        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    /**
     * @throws TraderException
     */
    public function test_get_tti_id_success(): void
    {
        Soap::fake(function () {
            return Soap::response([
                'ttiId' => '1',
                'errorCode' => '',
                'errorMessage' => '',
            ], 200);
        });

        $response = (new DmccV1Driver())->getTtiId(self::$order);

        $this->assertIsString($response);
        $this->assertEquals(1, $response);
    }

    /**
     * @throws TraderException
     */
    public function test_get_tti_id_with_invalid_response_fails(): void
    {
        $activityLogCount = Activity::query()->count();

        Soap::fake(function () {
            return Soap::response([
                'ttiId' => '',
                'errorCode' => '',
                'errorMessage' => 'error',
            ], 200);
        });
        $this->expectException(TraderException::class);

        (new DmccV1Driver())->getTtiId(self::$order);

        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    /**
     * @throws TraderException
     */
    public function test_cancel_order_success(): void
    {
        Soap::fake(function () {
            return Soap::response([
                'successCode' => '0000',
            ], 200);
        });

        $response = (new DmccV1Driver())->cancelOrder(self::$order);

        $this->assertEquals('0000', $response->successCode);
    }

    /**
     * @throws TraderException
     */
    public function test_cancel_order_fail(): void
    {
        $this->expectException(TraderException::class);

        $activityLogCount = Activity::query()->count();

        Soap::fake(function () {
            return Soap::response([
                'successCode' => '',
            ], 200);
        });

        (new DmccV1Driver())->cancelOrder(self::$order);

        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    /**
     * @throws TraderException
     */
    public function test_respond_ptp_service_success(): void
    {
        Soap::fake(function () {
            return Soap::response([
                'successCode' => '0000',
            ], 200);
        });

        $response = (new DmccV1Driver())->respondPtpService(self::$order);

        $this->assertEquals('0000', $response->successCode);
    }

    /**
     * @throws TraderException
     */
    public function test_respond_ptp_service_with_invalid_response_fails(): void
    {
        $this->expectException(TraderException::class);

        $activityLogCount = Activity::query()->count();

        Soap::fake(function () {
            return Soap::response([
                'successCode' => '',
            ], 200);
        });

        (new DmccV1Driver())->respondPtpService(self::$order);

        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    /**
     * @throws TraderException
     */
    public function test_create_selling_commodity_to_customer_document_success(): void
    {
        Storage::fake();
        UploadedFile::fake();
        Event::fake();

        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToStep(MurabhaStep::ContractSigned);

        (new DmccV1Driver())->createSellingCommodityToCustomerDocument(self::$traderOrder);

        $this->assertNotNull(self::$traderOrder->getFirstMediaUrl(TraderOrderMediaCollection::SellingCommodityToCustomer));
    }

    /**
     * @throws TraderException
     */
    public function test_create_selling_commodity_to_customer_document_with_invalid_trader_order_fails(): void
    {
        Storage::fake();
        UploadedFile::fake();

        $this->expectException(TraderException::class);

        $activityLogCount = Activity::query()->count();

        (new DmccV1Driver())->createSellingCommodityToCustomerDocument(new TraderOrder());

        $this->assertNull(self::$traderOrder->getFirstMediaUrl(TraderOrderMediaCollection::SellingCommodityToCustomer));
        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    /**
     * @throws TraderException
     */
    public function test_create_transfer_ownership_to_lender_document_success(): void
    {
        Storage::fake();
        UploadedFile::fake();

        (new DmccV1Driver())->createTransferOwnershipToLenderDocument(self::$traderOrder);

        $this->assertNotNull(self::$traderOrder->getFirstMediaUrl(TraderOrderMediaCollection::TransferOwnershipToLender));
    }

    /**
     * @throws TraderException
     */
    public function test_create_transfer_ownership_to_lender_document_with_invalid_trader_order_fails(): void
    {
        Storage::fake();
        UploadedFile::fake();

        $this->expectException(TraderException::class);

        $activityLogCount = Activity::query()->count();

        (new DmccV1Driver())->createTransferOwnershipToLenderDocument(new TraderOrder());

        $this->assertNull(self::$traderOrder->getFirstMediaUrl(TraderOrderMediaCollection::TransferOwnershipToLender));
        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    /**
     * @throws TraderException
     */
    public function test_get_document_by_type_and_transaction_success(): void
    {
        Soap::fake(function () {
            return Soap::response([
                'getdocument' => [
                    [
                        'getDocumentByTypeResponse' => [
                            [
                                'document' => 'document',
                            ],
                        ],
                    ],
                ],
            ], 200);
        });

        $response = (new DmccV1Driver())->getDocumentByTypeAndTransaction(1, 'documentType');

        $this->assertEquals('document', $response);
    }

    /**
     * @throws TraderException
     */
    public function test_get_document_by_type_and_transaction_with_invalid_response_fails(): void
    {
        $this->expectException(TraderException::class);

        $activityLogCount = Activity::query()->count();

        Soap::fake(function () {
            return Soap::response([
                'getdocument' => [
                    [
                        'getDocumentByTypeResponse' => [],
                    ],
                ],
            ], 200);
        });

        (new DmccV1Driver())->getDocumentByTypeAndTransaction(1, 'documentType');

        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    /**
     * @throws TraderException
     */
    public function test_get_inventory_basket_success(): void
    {
        Soap::fake(function () {
            return Soap::response([
                'inventoryDetails' => [
                    [
                        'hsCodeDescription' => 'hsCodeDescription',
                        'quantity' => 100,
                        'totalValue' => 100,
                        'currency' => 'SAR',
                        'warehouseOrVaultId' => 'warehouseOrVaultId',
                        'owner' => 'owner',
                        'previousOwner' => 'previousOwner',
                        'newOwner' => 'newOwner',
                        'inventoryRecordId' => 'inventoryRecordId',
                        'warrantPercentage' => 'warrantPercentage',
                        'warehouseOrVaultOperatorId' => 'warehouseOrVaultOperatorId',
                        'warrantNo' => 'warrantNo',
                        'uom' => 'uom',
                        'hsCode' => 'hsCode',
                        'dateTimeOfPurchasingCommodity' => Carbon::now()->format('d/m/Y H:i A'),
                        'warehouseOrVaultEmirates' => 'warehouseOrVaultEmirates',
                        'warehouseOrVaultCountry' => 'warehouseOrVaultCountry',
                    ],
                ],
                'exchangeRate' => 'exchangeRate',
                'errorCode' => '',
            ], 200);
        });

        $response = (new DmccV1Driver())->getInventoryBasket(self::$traderOrder);

        $this->assertEquals('', $response->errorCode);
    }

    /**
     * @throws TraderException
     */
    public function test_get_inventory_basket_fail(): void
    {
        $this->expectException(TraderException::class);

        $activityLogCount = Activity::query()->count();

        Soap::fake(function () {
            return Soap::response([
                'errorCode' => 'error',
            ], 200);
        });

        (new DmccV1Driver())->getInventoryBasket(self::$traderOrder);

        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    /**
     * @throws TraderException
     */
    public function test_upload_tti_document_and_get_version_number_success(): void
    {
        Soap::fake(function () {
            return Soap::response([
                'versionNo' => 1,
            ], 200);
        });

        $response = (new DmccV1Driver())->uploadTTIDocumentAndGetVersionNumber('1');

        $this->assertEquals(1, $response);
    }

    /**
     * @throws TraderException
     */
    public function test_upload_tti_document_and_get_version_number_fail(): void
    {
        $this->expectException(TraderException::class);

        $activityLogCount = Activity::query()->count();

        Soap::fake(function () {
            return Soap::response([
                'errorCode' => 'error',
            ], 200);
        });

        (new DmccV1Driver())->uploadTTIDocumentAndGetVersionNumber('1');

        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    /**
     * @throws TraderException
     */
    public function test_issue_murabaha_purchase_offer_success(): void
    {
        $activityLogCount = Activity::query()->count();

        Soap::fake(function () {
            return Soap::response([
                'successCode' => '0000',
            ], 200);
        });

        (new DmccV1Driver())->issueMurabahaPurchaseOffer(1, 1);

        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount);
    }

    /**
     * @throws TraderException
     */
    public function test_issue_murabaha_purchase_offer_with_invalid_response_fails(): void
    {
        $this->expectException(TraderException::class);

        $activityLogCount = Activity::query()->count();

        Soap::fake(function () {
            return Soap::response([
                'successCode' => '',
            ], 200);
        });

        (new DmccV1Driver())->issueMurabahaPurchaseOffer(1, 1);

        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }
}
