<?php

namespace Tests\Unit\Traders;

use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Exceptions\TraderException;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderHistory;
use App\Models\TraderOrder;
use App\Models\User;
use App\Support\Traders\Drivers\DmccDriver;
use CodeDredd\Soap\Facades\Soap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class DmccDriverTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    protected static Company $company;

    protected static User $lender;

    protected static FinancingOrder $order;

    protected function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany();
        self::$lender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$order = $this->createOrder(self::$company->id, self::$lender->id, [
            'status' => FinancingOrderStatus::Approved,
        ]);
    }

    /**
     * @return void
     *
     * @throws TraderException
     */
    public function test_get_tti_success(): void
    {
        Soap::fake(function () {
            return Soap::response([
                'ttiId' => '1',
                'errorCode' => '',
                'errorMessage' => '',
            ], 200);
        });

        (new DmccDriver())->getTti(self::$order);

        $this->assertDatabaseCount((new TraderOrder())->getTable(), 1);
        $this->assertDatabaseCount((new TraderHistory())->getTable(), 1);
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
}
