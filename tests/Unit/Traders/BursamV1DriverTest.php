<?php

namespace Tests\Unit\Traders;

use App\Enums\BursamErrorCode;
use App\Enums\BursamMurabhaStep;
use App\Enums\BursamProductCode;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\TraderOrderStatus;
use App\Exceptions\TraderException;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderHistory;
use App\Models\TraderOrder;
use App\Models\User;
use App\Support\Traders\Contracts\TraderInterface;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamStbCertificateAfterCancellation;
use App\Support\Traders\Drivers\Bursam\Strategies\BursamV1Driver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\Support\FinancingOrders\TraderOrderScenario;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class BursamV1DriverTest extends TestCase
{
    use RefreshDatabase, InteractsWithCompany, InteractsWithUser, WithFaker;

    protected static Company $company;

    protected static User $lender;

    protected static FinancingOrder $order;

    protected static Model|TraderOrder $traderOrder;

    protected static string $driverClass = BursamV1Driver::class;

    protected static TraderInterface $driver;

    protected static string $version = 'v1';

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

        self::$driver = new static::$driverClass();

        $data = [
            'product_code' => $this->faker->randomElement(BursamProductCode::getValues()),
            'provider' => 'bursam',
            'version' => static::$version,
            'uuid_one' => Str::uuid(),
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

        $data['exchange_rate'] = '1';

        self::$traderOrder = InProgressOrder::of(self::$order)->createTraderOrder(data: $data);
    }

    public function test_get_or_initiate_trader_oder_if_has_trader_order_success()
    {
        self::$traderOrder->update(['status' => TraderOrderStatus::Initiated]);
        $traderOrderCount = TraderOrder::query()->count();

        $traderOrder = self::$driver->getOrInitiateTraderOrder(self::$order);

        $this->assertDatabaseCount((new TraderOrder())->getTable(), $traderOrderCount);
        $this->assertEquals($traderOrder->id, self::$traderOrder->id);
    }

    public function test_get_or_initiate_trader_order_if_has_no_trader_order_success()
    {
        $traderOrderCount = TraderOrder::query()->count();

        $order = OrderScenario::inProgress()
            ->lender(self::$company)
            ->creator(self::$lender)
            ->commit()
            ->model();

        $traderOrder = self::$driver->getOrInitiateTraderOrder($order);

        $this->assertDatabaseCount((new TraderOrder())->getTable(), $traderOrderCount + 1);
        $this->assertInstanceOf(TraderOrder::class, $traderOrder);
    }

    /**
     * @throws TraderException
     */
    public function test_create_trader_order_success(): void
    {
        Event::fake();
        $traderOrderCount = TraderOrder::query()->count();
        $traderOrderHistoryCount = TraderHistory::query()->count();

        Http::fake(function () {
            return Http::response([], 200);
        });

        self::$driver->createTraderOrder(self::$order);

        $this->assertDatabaseCount((new TraderOrder())->getTable(), $traderOrderCount + 1);
        $this->assertDatabaseCount((new TraderHistory())->getTable(), $traderOrderHistoryCount + 1);
    }

    /**
     * @throws TraderException
     */
    public function test_process_order_with_error_code_fails(): void
    {
        $this->expectException(TraderException::class);

        $activityLogCount = Activity::query()->count();
        Http::fake(function () {
            return Http::response([
                'header' => [
                    'errorCode' => 'bursa is down',
                ],
            ], 200);
        });

        self::$driver->createTraderOrder(self::$order);

        $this->assertDatabaseCount((new TraderOrder())->getTable(), 2);
        $this->assertDatabaseCount((new TraderHistory())->getTable(), 0);
        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    /**
     * @throws TraderException
     */
    public function test_cancel_order_success(): void
    {
        Queue::fake();

        Http::fake(function () {
            return Http::response([
                'body' => [
                    ['statusCode' => 0],
                ],
            ], 200);
        });

        $response = self::$driver->cancelOrder(self::$order);

        Queue::assertPushed(ProcessBursamStbCertificateAfterCancellation::class);
        $this->assertTrue($response);
    }

    /**
     * @throws TraderException
     */
    public function test_cancel_order_fail(): void
    {
        Queue::fake();

        $this->expectException(TraderException::class);

        $activityLogCount = Activity::query()->count();

        Http::fake(function () {
            return Http::response([
                'header' => [
                    'errorCode' => 'unable to sell the commodity',
                ],
            ], 200);
        });

        self::$driver->cancelOrder(self::$order);

        Queue::assertNotPushed(ProcessBursamStbCertificateAfterCancellation::class);
        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    public function test_cancel_trader_order_manual_mode()
    {
        Event::fake();
        TraderOrderScenario::of(self::$traderOrder)
            ->reset();

        $result = self::$driver->cancelTraderOrder(self::$traderOrder);

        $this->assertTrue($result);
        $this->assertTrue(self::$traderOrder->status->is(TraderOrderStatus::Cancelled));
        $this->assertTrue(self::$traderOrder->order->status->is(FinancingOrderStatus::Cancelled));
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
            ->moveToStep(BursamMurabhaStep::ContractSigned);

        self::$driver->createSellingCommodityToCustomerDocument(self::$traderOrder);

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

        self::$driver->createSellingCommodityToCustomerDocument(new TraderOrder());

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

        self::$driver->createTransferOwnershipToLenderDocument(self::$traderOrder);

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

        self::$driver->createTransferOwnershipToLenderDocument(new TraderOrder());

        $this->assertNull(self::$traderOrder->getFirstMediaUrl(TraderOrderMediaCollection::TransferOwnershipToLender));
        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    public function test_fetch_order_result_ynn_success()
    {
        Event::fake();
        $traderOrderHistoryCount = TraderHistory::query()->count();

        Http::fake(function () {
            return Http::response([
                'status' => [
                    'processingCount' => 0,
                ],
                'body' => [
                    [
                        'bidErrNo' => '999',
                        'ecertNo' => Str::uuid(),
                    ],
                ],
            ], 200);
        });

        self::$driver->fetchOrderResultYNN(self::$traderOrder);

        $this->assertDatabaseCount((new TraderHistory())->getTable(), $traderOrderHistoryCount + 1);
    }

    public function test_fetch_order_result_ynn_if_product_is_not_available_fails()
    {
        Cache::spy();

        Http::fake(function () {
            return Http::response([
                'body' => [
                    [
                        'bidErrNo' => $this->faker->randomElement(BursamErrorCode::UNAVAILABLE_PRODUCT_ERROR_CODES),
                        'productCode' => self::$traderOrder->product_code,
                    ],
                ],
            ], 200);
        });

        $this->expectException(TraderException::class);

        self::$driver->fetchOrderResultYNN(self::$traderOrder);

        Cache::shouldHaveReceived('put')
            ->once()
            ->with('bursam_unavailable_product_codes', self::$traderOrder->product_code);
    }

    public function test_fetch_order_result_ynn_fails()
    {
        Http::fake(function () {
            return Http::response([
                'status' => [
                    'processingCount' => rand(0, 1),
                ],
                'body' => [
                    [
                        'bidErrNo' => rand(0, 998),
                    ],
                ],
            ], 200);
        });

        $this->expectException(TraderException::class);

        self::$driver->fetchOrderResultYNN(self::$traderOrder);
    }

    public function test_fetch_order_result_nyy_success()
    {
        Event::fake();
        $traderOrderHistoryCount = TraderHistory::count();
        Http::fake(function () {
            return Http::response([
                'status' => [
                    'processingCount' => 0,
                ],
                'body' => [
                    [
                        'otcErrNo' => '999',
                        'stbErrNo' => '999',
                    ],
                ],
            ], 200);
        });

        self::$driver->fetchOrderResultNYY(self::$traderOrder);

        $this->assertDatabaseCount((new TraderHistory())->getTable(), $traderOrderHistoryCount + 1);
    }

    public function test_fetch_order_result_nyy_fails()
    {
        Http::fake(function () {
            return Http::response([
                'status' => [
                    'processingCount' => 0,
                ],
                'body' => [
                    [
                        'otcErrNo' => rand(0, 998),
                        'stbErrNo' => rand(0, 998),
                    ],
                ],
            ], 200);
        });

        $this->expectException(TraderException::class);

        self::$driver->fetchOrderResultNYY(self::$traderOrder);
    }

    public function test_selling_commodity_to_bursam_success()
    {
        Http::fake(function () {
            return Http::response([
                'body' => [
                    ['statusCode' => 0],
                ],
            ], 200);
        });

        $response = self::$driver->sellCommodityToBursam(self::$traderOrder);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertNotNull(self::$traderOrder->uuid_two);
    }

    public function test_selling_commodity_to_bursam_fails()
    {
        Http::fake(function () {
            return Http::response([
                'header' => [
                    'errorCode' => 'unable to sell the commodity',
                ],
            ], 200);
        });

        $this->expectException(TraderException::class);

        self::$driver->sellCommodityToBursam(self::$traderOrder);
    }

    public function test_get_bid_certificate_details_success()
    {
        Storage::fake();
        UploadedFile::fake();
        Http::fake(function () {
            return Http::response([
                'ECERTNO' => 'OLN03SEP23-0000002-000',
                'BUYER' => 'LYNK LLC',
                'OWNER' => 'LYNK LLC',
                'BIDNO' => '12',
                'TOTALVALUE' => '1.00',
                'CURRENCY' => 'SAR',
                'PRICE' => '4,680.51675978',
                'PRICE_MYR_EQUIVALENT' => '5,362.00',
                'PURCHASETIMEDATE' => '10:29:31.703 03 Sep 2023',
                'VALUEDATE' => '03 Sep 2023',
                'PNAME' => 'OLN-MSIA-12',
                'PVOLUME' => '0.00021447',
                'LINE' => [
                    [
                        'SUPPLIER' => 'CSP 10',
                        'VOLUME' => '0.00021447',
                    ],
                ],
            ]);
        });
        self::$traderOrder->update(['original_data' => ['unit' => 'Tonnages']]);

        self::$driver->getBidCertificateDetails(self::$traderOrder);

        $this->assertNotNull(self::$traderOrder->getFirstMediaUrl(TraderOrderMediaCollection::TtiHoldingCertificate));
    }

    public function test_get_bid_certificate_details_fails()
    {
        Http::fake(function () {
            return Http::response([
                'SUCCESSYN' => 'N',
            ]);
        });

        $this->expectException(TraderException::class);

        self::$driver->getBidCertificateDetails(self::$traderOrder);
    }

    public function test_get_otc_certificate_details_success()
    {
        Storage::fake();
        UploadedFile::fake();
        Http::fake(function () {
            return Http::response([
                'ECERTNO' => 'OLN03SEP23-0000002-000',
                'BUYER' => 'LYNK LLC',
                'OWNER' => 'LYNK LLC',
                'BIDNO' => '12',
                'TOTALVALUE' => '1.00',
                'CURRENCY' => 'SAR',
                'PRICE' => '4,680.51675978',
                'PRICE_MYR_EQUIVALENT' => '5,362.00',
                'PURCHASETIMEDATE' => '10:29:31.703 03 Sep 2023',
                'VALUEDATE' => '03 Sep 2023',
                'PNAME' => 'OLN-MSIA-12',
                'PVOLUME' => '0.00021447',
                'LINE' => [
                    [
                        'SUPPLIER' => 'CSP 10',
                        'VOLUME' => '0.00021447',
                    ],
                ],
            ]);
        });
        self::$traderOrder->update(['original_data' => ['unit' => 'Tonnages']]);

        self::$driver->getOtcCertificateDetails(self::$traderOrder);

        $this->assertNotNull(self::$traderOrder->getFirstMediaUrl(TraderOrderMediaCollection::BursamSellingCommodityToCustomer));
    }

    public function test_get_otc_certificate_details_fails()
    {
        Http::fake(function () {
            return Http::response([
                'SUCCESSYN' => 'N',
            ]);
        });

        $this->expectException(TraderException::class);

        self::$driver->getOtcCertificateDetails(self::$traderOrder);
    }

    public function test_get_stb_certificate_details_success()
    {
        Storage::fake();
        UploadedFile::fake();
        Http::fake(function () {
            return Http::response([
                'ECERTNO' => 'OLN03SEP23-0000002-000',
                'BUYER' => 'LYNK LLC',
                'OWNER' => 'LYNK LLC',
                'BIDNO' => '12',
                'TOTALVALUE' => '1.00',
                'CURRENCY' => 'SAR',
                'PRICE' => '4,680.51675978',
                'PRICE_MYR_EQUIVALENT' => '5,362.00',
                'PURCHASETIMEDATE' => '10:29:31.703 03 Sep 2023',
                'VALUEDATE' => '03 Sep 2023',
                'PNAME' => 'OLN-MSIA-12',
                'PVOLUME' => '0.00021447',
                'LINE' => [
                    [
                        'SUPPLIER' => 'CSP 10',
                        'VOLUME' => '0.00021447',
                    ],
                ],
            ]);
        });
        self::$traderOrder->update(['original_data' => ['unit' => 'Tonnages']]);

        self::$driver->getStbCertificateDetails(self::$traderOrder);

        $this->assertNotNull(self::$traderOrder->getFirstMediaUrl(TraderOrderMediaCollection::BursamTtiHoldingCertificate));
    }

    public function test_get_stb_certificate_details_fails()
    {
        Http::fake(function () {
            return Http::response([
                'SUCCESSYN' => 'N',
            ]);
        });

        $this->expectException(TraderException::class);

        self::$driver->getStbCertificateDetails(self::$traderOrder);
    }

    public function test_is_trader_order_cancellable_success()
    {
        $this->assertTrue(self::$driver->isTraderOrderCancellable(self::$traderOrder, ''));
    }
}
