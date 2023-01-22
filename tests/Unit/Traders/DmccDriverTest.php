<?php

namespace Tests\Unit\Traders;

use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Enums\TraderOrderStatus;
use App\Exceptions\TraderException;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderHistory;
use App\Models\TraderOrder;
use App\Models\User;
use App\Support\Traders\Drivers\DmccDriver;
use Carbon\Carbon;
use CodeDredd\Soap\Facades\Soap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class DmccDriverTest extends TestCase
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
        self::$order = $this->createOrder(self::$company->id, self::$lender->id, [
            'status' => FinancingOrderStatus::Approved,
        ]);
        self::$traderOrder = TraderOrder::query()->create([
            'financing_order_id' => self::$order->id,
            'reference' => 1,
            'provider' => 'dmcc',
            'status' => TraderOrderStatus::InProgress,
            'amount' => 1,
            'product' => 'product',
            'quantity' => 1,
            'warehouse' => 'warehouse',
            'owner' => 'owner',
            'dateTimeOfPurchasingCommodity' => Carbon::now()->format('d/m/Y H:i A'),
        ]);
    }

    /**
     * @return void
     *
     * @throws TraderException
     */
    public function test_get_tti_success(): void
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

        (new DmccDriver())->getTti(self::$order);

        $this->assertDatabaseCount((new TraderOrder())->getTable(), $traderOrderCount + 1);
        $this->assertDatabaseCount((new TraderHistory())->getTable(), $traderOrderHistoryCount + 1);
    }

    /**
     * @return void
     *
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

        (new DmccDriver())->getTti(self::$order);

        $this->assertDatabaseCount((new TraderOrder())->getTable(), 0);
        $this->assertDatabaseCount((new TraderHistory())->getTable(), 0);
        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    /**
     * @return void
     */
    public function test_accept_agreement_fail(): void
    {
        $this->expectException(RuntimeException::class);

        $activityLogCount = Activity::query()->count();

        Soap::fake(function () {
            return Soap::response([
                'ttiId' => '',
                'errorCode' => '',
                'errorMessage' => 'error',
            ], 200);
        });

        (new DmccDriver())->acceptAgreement();

        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    /**
     * @return void
     *
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

        $response = (new DmccDriver())->fetchNotifications('ACTIONABLE');

        $this->assertIsArray($response);
    }

    /**
     * @return void
     *
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

        (new DmccDriver())->fetchNotifications('ACTIONABLE');

        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    /**
     * @return void
     *
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

        $response = (new DmccDriver())->getTtiId(self::$order);

        $this->assertIsString($response);
        $this->assertEquals(1, $response);
    }

    /**
     * @return void
     *
     * @throws TraderException
     */
    public function test_get_tti_id_with_invalid_response_fails(): void
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

        (new DmccDriver())->getTtiId(self::$order);

        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    /**
     * @return void
     *
     * @throws TraderException
     */
    public function test_cancel_order_success(): void
    {
        Soap::fake(function () {
            return Soap::response([
                'successCode' => '0000',
            ], 200);
        });

        $response = (new DmccDriver())->cancelOrder(self::$order);

        $this->assertEquals('0000', $response->successCode);
    }

    /**
     * @return void
     *
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

        (new DmccDriver())->cancelOrder(self::$order);

        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    /**
     * @return void
     *
     * @throws TraderException
     */
    public function test_respond_ptp_service_success(): void
    {
        Soap::fake(function () {
            return Soap::response([
                'successCode' => '0000',
            ], 200);
        });

        $response = (new DmccDriver())->respondPtpService(self::$order);

        $this->assertEquals('0000', $response->successCode);
    }

    /**
     * @return void
     *
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

        (new DmccDriver())->respondPtpService(self::$order);

        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    /**
     * @return void
     *
     * @throws TraderException
     */
    public function test_create_selling_commodity_to_customer_document_success(): void
    {
        Storage::fake();
        UploadedFile::fake();

        (new DmccDriver())->createSellingCommodityToCustomerDocument(self::$traderOrder);

        $this->assertNotNull(self::$order->getFirstMediaUrl(FinancingOrderMediaCollection::SellingCommodityToCustomer));
    }

    /**
     * @return void
     *
     * @throws TraderException
     */
    public function test_create_selling_commodity_to_customer_document_with_invalid_trader_order_fails(): void
    {
        Storage::fake();
        UploadedFile::fake();

        $this->expectException(TraderException::class);

        $activityLogCount = Activity::query()->count();

        (new DmccDriver())->createSellingCommodityToCustomerDocument(new TraderOrder());

        $this->assertNull(self::$order->getFirstMediaUrl(FinancingOrderMediaCollection::SellingCommodityToCustomer));
        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    /**
     * @return void
     *
     * @throws TraderException
     */
    public function test_create_transfer_ownership_to_lender_document_success(): void
    {
        Storage::fake();
        UploadedFile::fake();

        (new DmccDriver())->createTransferOwnershipToLenderDocument(self::$traderOrder);

        $this->assertNotNull(self::$order->getFirstMediaUrl(FinancingOrderMediaCollection::TransferOwnershipToLender));
    }

    /**
     * @return void
     *
     * @throws TraderException
     */
    public function test_create_transfer_ownership_to_lender_document_with_invalid_trader_order_fails(): void
    {
        Storage::fake();
        UploadedFile::fake();

        $this->expectException(TraderException::class);

        $activityLogCount = Activity::query()->count();

        (new DmccDriver())->createTransferOwnershipToLenderDocument(new TraderOrder());

        $this->assertNull(self::$order->getFirstMediaUrl(FinancingOrderMediaCollection::TransferOwnershipToLender));
        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    /**
     * @return void
     *
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

        $response = (new DmccDriver())->getDocumentByTypeAndTransaction(1, 'documentType');

        $this->assertEquals('document', $response);
    }

    /**
     * @return void
     *
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

        (new DmccDriver())->getDocumentByTypeAndTransaction(1, 'documentType');

        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    /**
     * @return void
     *
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
                        'dateTimeOfPurchasingCommodity' => Carbon::now()->format('d/m/Y H:i A'),
                        'warehouseOrVaultEmirates' => 'warehouseOrVaultEmirates',
                        'warehouseOrVaultCountry' => 'warehouseOrVaultCountry',
                    ],
                ],
                'errorCode' => '',
            ], 200);
        });

        $response = (new DmccDriver())->getInventoryBasket(self::$traderOrder);

        $this->assertEquals('', $response->errorCode);
    }

    /**
     * @return void
     *
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

        (new DmccDriver())->getInventoryBasket(self::$traderOrder);

        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    /**
     * @return void
     *
     * @throws TraderException
     */
    public function test_upload_tti_document_and_get_version_number_success(): void
    {
        Soap::fake(function () {
            return Soap::response([
                'versionNo' => 1,
            ], 200);
        });

        $response = (new DmccDriver())->uploadTTIDocumentAndGetVersionNumber('1');

        $this->assertEquals(1, $response);
    }

    /**
     * @return void
     *
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

        (new DmccDriver())->uploadTTIDocumentAndGetVersionNumber('1');

        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    /**
     * @return void
     *
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

        (new DmccDriver())->issueMurabahaPurchaseOffer(1, 1);

        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount);
    }

    /**
     * @return void
     *
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

        (new DmccDriver())->issueMurabahaPurchaseOffer(1, 1);

        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }
}
