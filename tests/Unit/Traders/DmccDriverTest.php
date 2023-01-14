<?php

namespace Tests\Unit\Traders;

use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\TraderOrderStatus;
use App\Exceptions\TraderException;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderHistory;
use App\Models\TraderOrder;
use App\Models\User;
use App\Support\Traders\Drivers\DmccDriver;
use CodeDredd\Soap\Facades\Soap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class DmccDriverTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    protected static Company $company;

    protected static User $lender;

    protected static FinancingOrder $order;

    protected static TraderOrder $traderOrder;

    protected function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany();
        self::$lender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$order = $this->createOrder(self::$company->id, self::$lender->id, [
            'status' => FinancingOrderStatus::Approved,
        ]);
        self::$traderOrder = self::$order->traderOrders()->create([
            'reference' => 1,
            'provider' => 'dmcc',
            'status' => TraderOrderStatus::InProgress,
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
    public function test_get_tti_fail(): void
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
    public function test_fetch_notifications_fail(): void
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
    public function test_get_tti_id_fail(): void
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
    public function test_respond_ptp_service_fail(): void
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
}
