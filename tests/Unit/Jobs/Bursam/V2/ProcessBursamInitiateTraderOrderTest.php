<?php

namespace Jobs\Bursam\V2;

use App\Actions\Contracts\Orders\TraderOrders\InitiateTraderOrder;
use App\Models\Company;
use App\Models\TraderOrder;
use App\Models\User;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamInitiateTraderOrder;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FinancingOrders\CommittedOrder;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class ProcessBursamInitiateTraderOrderTest extends TestCase
{
    use RefreshDatabase,  InteractsWithUser, InteractsWithCompany;

    protected static CommittedOrder $financingOrder;

    protected static Model|TraderOrder $traderOrder;

    protected static Company $lender;

    protected static User $userLender;

    public function setUp(): void
    {
        parent::setUp();

        [self::$lender] = $this->createLenderCompany('2000', ['company_cr' => '1234567891']);
        self::$userLender = $this->createLenderUser(self::$lender->id);

        self::$financingOrder = OrderScenario::inProgress()
            ->creator(self::$userLender)
            ->commit();
    }

    public function test_initiate_trader_order()
    {
        $response = (new ProcessBursamInitiateTraderOrder(self::$financingOrder->model()))->handle(app(InitiateTraderOrder::class));

        $this->assertEquals(Command::SUCCESS, $response);
    }

    public function test_initiate_trader_order_where_order_is_already_initiated()
    {
        InProgressOrder::of(self::$financingOrder)->createTraderOrder(driver: 'bursam', data: [
            'version' => 'v2',
        ]);

        $response = (new ProcessBursamInitiateTraderOrder(self::$financingOrder->model()))->handle(app(InitiateTraderOrder::class));

        $this->assertEquals(Command::SUCCESS, $response);
    }
}
