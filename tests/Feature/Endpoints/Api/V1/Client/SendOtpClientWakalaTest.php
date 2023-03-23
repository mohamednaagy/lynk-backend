<?php

namespace Endpoints\Api\V1\Client;

use App\Enums\FinancingOrderHistory;
use App\Enums\Role;
use App\Models\Company;
use App\Models\TraderOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\Support\FinancingOrders\CommittedOrder;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\Support\FinancingOrders\TraderOrderScenario;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class SendOtpClientWakalaTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany;

    private static Company $company;

    private static User $userLender;

    private static CommittedOrder $order;

    private static TraderOrder|Model $traderOrder;

    private static string $endpoint;

    public function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);

        self::$order = OrderScenario::inProgress()
            ->lender(self::$company)
            ->creator(self::$userLender)
            ->commit();

        self::$traderOrder = InProgressOrder::of(self::$order)->createTraderOrder();

        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::WaitingClientWakala);

        self::$endpoint = 'api/v1/client/wakala/access';
    }

    public function test_send_otp_client_wakala_successfully()
    {
        $this->postJson(self::$endpoint, [
            'national_id' => self::$order->national_id,
            'order_id' => self::$order->id,
        ])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => ['vid'],
            ]);
    }

    public function test_send_otp_client_wakala_with_invalid_national_id_will_fail()
    {
        $this->postJson(self::$endpoint, [
            'national_id' => '1591192305',
            'order_id' => self::$order->id,
        ])->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public function test_send_otp_client_wakala_with_already_verified_order_will_fail()
    {
        self::$traderOrder->traderHistories()->create([
            'action' => FinancingOrderHistory::ClientWakalaAccepted,
        ]);

        $this->postJson(self::$endpoint, [
            'national_id' => '2553451234',
            'order_id' => self::$order->id,
        ])
            ->assertStatus(Response::HTTP_BAD_REQUEST);
    }
}
