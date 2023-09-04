<?php

namespace Tests\Unit\Traders;

use App\Enums\Area;
use App\Enums\BursamErrorCode;
use App\Enums\BursamMurabhaStep;
use App\Enums\BursamProductCode;
use App\Enums\DmccMurabhaStep;
use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\TraderOrderMode;
use App\Enums\TraderOrderStatus;
use App\Exceptions\TraderException;
use App\Jobs\General\ProcessAskClientForWakala;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderHistory;
use App\Models\TraderOrder;
use App\Models\User;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamBidCertificate;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamOrderResultNYY;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamOrderResultYNN;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamOtcCertificate;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamSellingCommodityToOpenMarket;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamSellingCommodityToOpenMarketForCancellation;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamStbCertificate;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamStbCertificateAfterCancellation;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamTransferOwnershipToCustomer;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamTransferOwnershipToLender;
use App\Support\Traders\Drivers\Bursam\Strategies\BursamV2Driver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Support\Facades\Bus;
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

class BursamV2DriverTest extends TestCase
{
    use RefreshDatabase, InteractsWithCompany, InteractsWithUser, WithFaker;

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
            'product_code' => $this->faker->randomElement(BursamProductCode::getValues()),
            'provider' => 'bursam',
            'version' => 'v2',
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

        $traderOrder = (new BursamV2Driver())->getOrInitiateTraderOrder(self::$order);

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

        $traderOrder = (new BursamV2Driver())->getOrInitiateTraderOrder($order);

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

        (new BursamV2Driver())->createTraderOrder(self::$order);

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

        (new BursamV2Driver())->createTraderOrder(self::$order);

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

        $response = (new BursamV2Driver())->cancelOrder(self::$order);

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

        (new BursamV2Driver())->cancelOrder(self::$order);

        Queue::assertNotPushed(ProcessBursamStbCertificateAfterCancellation::class);
        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    public function test_cancel_trader_order_if_not_commodity_purchased_success()
    {
        Bus::fake();

        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::GetTtiHoldingCertificateDocument);

        $result = (new BursamV2Driver())->cancelTraderOrder(self::$traderOrder);

        $this->assertTrue($result);
        $this->assertTrue(self::$traderOrder->status->is(TraderOrderStatus::PendingCancellation));
        Bus::assertChained([
            ProcessBursamSellingCommodityToOpenMarketForCancellation::class,
            ProcessBursamStbCertificateAfterCancellation::class,
            CallQueuedClosure::class,
        ]);
    }

    public function test_cancel_trader_order_fails()
    {
        $this->expectException(\Exception::class);

        TraderOrderScenario::of(self::$traderOrder)
            ->reset();

        (new BursamV2Driver())->cancelTraderOrder(self::$traderOrder);
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
            ->moveToStep(DmccMurabhaStep::ContractSigned);

        (new BursamV2Driver())->createSellingCommodityToCustomerDocument(self::$traderOrder);

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

        (new BursamV2Driver())->createSellingCommodityToCustomerDocument(new TraderOrder());

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

        (new BursamV2Driver())->createTransferOwnershipToLenderDocument(self::$traderOrder);

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

        (new BursamV2Driver())->createTransferOwnershipToLenderDocument(new TraderOrder());

        $this->assertNull(self::$traderOrder->getFirstMediaUrl(TraderOrderMediaCollection::TransferOwnershipToLender));
        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    public function test_fetch_order_result_ynn_success()
    {
        $traderOrderHistoryCount = TraderHistory::query()->count();
        Event::fake();

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

        (new BursamV2Driver())->fetchOrderResultYNN(self::$traderOrder);

        $this->assertDatabaseCount((new TraderHistory())->getTable(), $traderOrderHistoryCount + 1);
    }

    public function test_fetch_order_result_ynn_if_product_is_not_available_fails()
    {
        Cache::spy();

        Http::fake(function () {
            return Http::response([
                'body' => [
                    [
                        'bidErrNo' => BursamErrorCode::UNAVAILABLE_PRODUCT_ERROR_CODES,
                        'productCode' => self::$traderOrder->product_code,
                    ],
                ],
            ], 200);
        });

        $this->expectException(TraderException::class);

        (new BursamV2Driver())->fetchOrderResultYNN(self::$traderOrder);

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

        (new BursamV2Driver())->fetchOrderResultYNN(self::$traderOrder);
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

        (new BursamV2Driver())->fetchOrderResultNYY(self::$traderOrder);

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

        (new BursamV2Driver())->fetchOrderResultNYY(self::$traderOrder);
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

        $response = (new BursamV2Driver())->sellCommodityToBursam(self::$traderOrder);

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

        (new BursamV2Driver())->sellCommodityToBursam(self::$traderOrder);
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

        (new BursamV2Driver())->getBidCertificateDetails(self::$traderOrder);

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

        (new BursamV2Driver())->getBidCertificateDetails(self::$traderOrder);
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

        (new BursamV2Driver())->getOtcCertificateDetails(self::$traderOrder);

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

        (new BursamV2Driver())->getOtcCertificateDetails(self::$traderOrder);
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

        (new BursamV2Driver())->getStbCertificateDetails(self::$traderOrder);

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

        (new BursamV2Driver())->getStbCertificateDetails(self::$traderOrder);
    }

    public function test_is_trader_order_cancellable_success()
    {
        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::GetTtiHoldingCertificateDocument);

        $this->assertTrue((new BursamV2Driver())->isTraderOrderCancellable(self::$traderOrder, null));
    }

    public function test_is_trader_order_cancellable_if_in_transition_state_fails()
    {
        TraderOrderScenario::of(self::$traderOrder)
            ->reset();

        $this->assertFalse((new BursamV2Driver())->isTraderOrderCancellable(self::$traderOrder, null));
    }

    public function test_is_trader_order_cancellable_if_in_contract_signed_state_fails()
    {
        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToStep(BursamMurabhaStep::ContractSigned);

        $this->assertFalse((new BursamV2Driver())->isTraderOrderCancellable(self::$traderOrder, Area::Lender));
    }

    public function test_dispatch_job_for_transitioning_flow_if_trading_mode_automatic_success()
    {
        Queue::fake();
        self::$traderOrder->update([
            'mode' => TraderOrderMode::Automatic,
        ]);

        self::$traderOrder = TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->getTraderOrder();

        (new BursamV2Driver())->dispatchJobForTransitioningFlow(self::$traderOrder);

        Queue::assertPushed(ProcessBursamOrderResultYNN::class);

        self::$traderOrder = TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::GetTtiHoldingCertificateDocument)
            ->getTraderOrder();

        (new BursamV2Driver())->dispatchJobForTransitioningFlow(self::$traderOrder);

        Queue::assertPushed(ProcessBursamBidCertificate::class);

        self::$traderOrder = TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::AttachTtiHoldingCertificateDocument)
            ->getTraderOrder();

        (new BursamV2Driver())->dispatchJobForTransitioningFlow(self::$traderOrder);

        Queue::assertPushed(ProcessBursamTransferOwnershipToLender::class);

        self::$traderOrder = TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::ContractSigned)
            ->getTraderOrder();

        (new BursamV2Driver())->dispatchJobForTransitioningFlow(self::$traderOrder);

        Queue::assertPushed(ProcessBursamTransferOwnershipToCustomer::class);

        self::$traderOrder = TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::CreateSellingCommodityToCustomerDocument)
            ->getTraderOrder();

        (new BursamV2Driver())->dispatchJobForTransitioningFlow(self::$traderOrder);

        Queue::assertPushed(ProcessAskClientForWakala::class);

        self::$traderOrder = TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::ClientWakalaAccepted)
            ->getTraderOrder();

        (new BursamV2Driver())->dispatchJobForTransitioningFlow(self::$traderOrder);

        Queue::assertPushed(ProcessBursamSellingCommodityToOpenMarket::class);

        self::$traderOrder = TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument)
            ->getTraderOrder();

        (new BursamV2Driver())->dispatchJobForTransitioningFlow(self::$traderOrder);

        Queue::assertPushed(ProcessBursamOrderResultNYY::class);

        self::$traderOrder = TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::CommoditySoldToMarket)
            ->getTraderOrder();

        (new BursamV2Driver())->dispatchJobForTransitioningFlow(self::$traderOrder);

        Queue::assertPushed(ProcessBursamOtcCertificate::class);

        self::$traderOrder = TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::GetOwnershipToCustomerCertificate)
            ->getTraderOrder();

        (new BursamV2Driver())->dispatchJobForTransitioningFlow(self::$traderOrder);

        Queue::assertPushed(ProcessBursamStbCertificate::class);
    }
}
