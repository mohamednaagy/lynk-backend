<?php

namespace Tests\Unit\Traders;

use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\TraderOrderStatus;
use App\Exceptions\TraderException;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderHistory;
use App\Models\TraderOrder;
use App\Models\User;
use App\Support\Traders\Drivers\FakeDriver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class FakeDriverTest extends TestCase
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

        Http::fake(function () {
            return Http::response([
                'data' => ['ttiId' => '1'],
            ], 200);
        });

        (new FakeDriver())->getTti(self::$order);

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
        Http::fake(function () {
            return Http::response([
                'data' => [],
            ], 422);
        });

        (new FakeDriver())->getTti(self::$order);

        $this->assertDatabaseCount((new TraderOrder())->getTable(), 0);
        $this->assertDatabaseCount((new TraderHistory())->getTable(), 0);
        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    /**
     * @return void
     */
    public function test_accept_agreement_success(): void
    {
        $this->assertTrue((new FakeDriver())->acceptAgreement());
    }

    /**
     * @return void
     *
     * @throws TraderException
     */
    public function test_fetch_notifications_success(): void
    {
        Http::fake(function () {
            return Http::response([
                [
                    'id' => '123',
                    'notification' => '',
                    'ttiId' => '1',
                ],
            ], 200);
        });

        $response = (new FakeDriver())->fetchNotifications('ACTIONABLE');

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

        Http::fake(function () {
            return Http::response([], 500);
        });

        (new FakeDriver())->fetchNotifications('ACTIONABLE');

        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    /**
     * @return void
     *
     * @throws TraderException
     */
    public function test_get_tti_id_success(): void
    {
        Http::fake(function () {
            return Http::response([
                'data' => ['ttiId' => '1'],
            ], 200);
        });

        $response = (new FakeDriver())->getTtiId(self::$order);

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

        Http::fake(function () {
            return Http::response([
                'data' => [],
            ], 422);
        });

        (new FakeDriver())->getTtiId(self::$order);

        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    /**
     * @return void
     *
     * @throws TraderException
     */
    public function test_cancel_order_success(): void
    {
        $response = (new FakeDriver())->cancelOrder(self::$order);

        $this->assertTrue($response);
    }

    /**
     * @return void
     *
     * @throws TraderException
     */
    public function test_respond_ptp_service_success(): void
    {
        Http::fake(function () {
            return Http::response([
                'data' => [],
            ], 200);
        });

        $response = (new FakeDriver())->respondPtpService(self::$order);

        $this->assertEquals((object) ['data' => []], $response);
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

        Http::fake(function () {
            return Http::response([
                'data' => [],
            ], 422);
        });

        (new FakeDriver())->respondPtpService(self::$order);

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

        (new FakeDriver())->createSellingCommodityToCustomerDocument(self::$traderOrder);

        $this->assertNotNull(self::$traderOrder->getFirstMediaUrl(TraderOrderMediaCollection::SellingCommodityToCustomer));
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

        (new FakeDriver())->createSellingCommodityToCustomerDocument(new TraderOrder());

        $this->assertNull(self::$traderOrder->getFirstMediaUrl(TraderOrderMediaCollection::SellingCommodityToCustomer));
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

        (new FakeDriver())->createTransferOwnershipToLenderDocument(self::$traderOrder);

        $this->assertNotNull(self::$traderOrder->getFirstMediaUrl(TraderOrderMediaCollection::TransferOwnershipToLender));
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

        (new FakeDriver())->createTransferOwnershipToLenderDocument(new TraderOrder());

        $this->assertNull(self::$traderOrder->getFirstMediaUrl(TraderOrderMediaCollection::TransferOwnershipToLender));
        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    /**
     * @return void
     *
     * @throws TraderException
     */
    public function test_get_document_by_type_and_transaction_success(): void
    {
        Http::fake(function () {
            return Http::response([
                'data' => [
                    'fileContent' => 'document',
                ],
            ], 200);
        });

        $response = (new FakeDriver())->getDocumentByTypeAndTransaction(1, 'documentType');

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

        Http::fake(function () {
            return Http::response([], 500);
        });

        (new FakeDriver())->getDocumentByTypeAndTransaction(1, 'documentType');

        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    /**
     * @return void
     *
     * @throws TraderException
     */
    public function test_get_inventory_basket_success(): void
    {
        $response = (new FakeDriver())->getInventoryBasket(self::$traderOrder);

        $this->assertEquals((object) [
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
            'exchange_rate' => '3.75',
        ], $response);
    }

    /**
     * @return void
     *
     * @throws TraderException
     */
    public function test_upload_tti_document_and_get_version_number_success(): void
    {
        $response = (new FakeDriver())->uploadTTIDocumentAndGetVersionNumber('1');

        $this->assertEquals('001', $response);
    }

    /**
     * @return void
     *
     * @throws TraderException
     */
    public function test_issue_murabaha_purchase_offer_success(): void
    {
        $activityLogCount = Activity::query()->count();

        Http::fake(function () {
            return Http::response([], 200);
        });

        (new FakeDriver())->issueMurabahaPurchaseOffer(1, 1);

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

        Http::fake(function () {
            return Http::response([], 422);
        });

        (new FakeDriver())->issueMurabahaPurchaseOffer(1, 1);

        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }
}
