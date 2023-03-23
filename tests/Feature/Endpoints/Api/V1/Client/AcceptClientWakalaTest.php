<?php

namespace Tests\Feature\Endpoints\Api\V1\Client;

use App\Enums\FinancingOrderHistory;
use App\Enums\Role;
use App\Models\Company;
use App\Models\TraderOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Otpify\Models\OtpifyCode;
use Symfony\Component\HttpFoundation\Response;
use Tests\Support\FinancingOrders\CommittedOrder;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\Support\FinancingOrders\TraderOrderScenario;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class AcceptClientWakalaTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany;

    private static Company $company;

    private static User $userLender;

    private static CommittedOrder $order;

    private static TraderOrder|Model $traderOrder;

    private static OtpifyCode $otpifyCode;

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
    }

    public function test_accept_client_wakala_successful()
    {
        $cacheKey = sprintf('client_wakala_token_%s_%s', self::$order->id, self::$order->national_id);

        $token = Str::random(100);

        Cache::put(
            $cacheKey,
            Hash::make($token),
            now()->addMinutes(10)
        );

        $this->withToken($token)
            ->postJson('api/v1/client/wakala/accept', [
                'national_id' => self::$order->national_id,
                'order_id' => self::$order->id,
            ])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => ['wakala_file_url'],
            ]);

        $this->assertNull(Cache::get($cacheKey));
    }

    public function test_accept_client_wakala_with_invalid_national_id_nothing_work()
    {
        $cacheKey = sprintf('client_wakala_token_%s_%s', self::$order->id, self::$order->national_id);

        $token = Str::random(100);

        Cache::put(
            $cacheKey,
            Hash::make($token),
            now()->addMinutes(10)
        );

        $this->withToken($token)
            ->postJson('api/v1/client/wakala/accept', [
                'national_id' => '1591192305',
                'order_id' => self::$order->id,
            ])
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function test_accept_client_wakala_with_already_verified_order_nothing_work()
    {
        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::ClientWakalaAccepted);

        $cacheKey = sprintf('client_wakala_token_%s_%s', self::$order->id, self::$order->national_id);

        $token = Str::random(100);

        Cache::put(
            $cacheKey,
            Hash::make($token),
            now()->addMinutes(10)
        );

        $this->withToken($token)
            ->postJson('api/v1/client/wakala/accept', [
                'national_id' => self::$order->national_id,
                'order_id' => self::$order->id,
            ])
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function test_accept_client_wakala_with_token_expired_nothing_work()
    {
        $cacheKey = sprintf('client_wakala_token_%s_%s', self::$order->id, self::$order->national_id);

        $token = Str::random(100);

        Cache::put(
            $cacheKey,
            Hash::make($token),
            now()->addSecond()
        );

        sleep(2);

        $this->withToken($token)
            ->postJson('api/v1/client/wakala/accept', [
                'national_id' => self::$order->national_id,
                'order_id' => self::$order->id,
            ])
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }
}
