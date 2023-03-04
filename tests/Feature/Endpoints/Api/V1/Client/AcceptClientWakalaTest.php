<?php

namespace Tests\Feature\Endpoints\Api\V1\Client;

use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Otpify\Models\OtpifyCode;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class AcceptClientWakalaTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany;

    private static Company $company;

    private static User $userLender;

    private static FinancingOrder|Model $order;

    private static OtpifyCode $otpifyCode;

    public function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);

        self::$order = $this->createOrder(self::$company->id, self::$userLender->id, [
            'national_id' => '2553451234',
        ]);
        self::$order->traderOrders()->create([
            'provider' => 'fake',
            'status' => TraderOrderStatus::InProgress,
            'reference' => 123,
        ]);
    }

    public function test_accept_client_wakala_successful()
    {
        $cacheKey = sprintf('client_wakala_token_%s_%s', self::$order->id, self::$order->getNationalId());

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

        $this->assertTrue(self::$order->fresh()->status->is(FinancingOrderStatus::ClientWakalaCompleted));
    }

    public function test_accept_client_wakala_with_invalid_national_id_nothing_work()
    {
        $cacheKey = sprintf('client_wakala_token_%s_%s', self::$order->id, self::$order->getNationalId());

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
        self::$order->update(['client_wakala_accepted_at' => now()]);

        $cacheKey = sprintf('client_wakala_token_%s_%s', self::$order->id, self::$order->getNationalId());

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
        $cacheKey = sprintf('client_wakala_token_%s_%s', self::$order->id, self::$order->getNationalId());

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
