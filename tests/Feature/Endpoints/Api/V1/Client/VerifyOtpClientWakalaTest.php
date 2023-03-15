<?php

namespace Endpoints\Api\V1\Client;

use App\Enums\FinancingOrderHistory;
use App\Enums\Role;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Otpify\Facades\Otpify;
use Modules\Otpify\Models\OtpifyCode;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class VerifyOtpClientWakalaTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany;

    private static Company $company;

    private static User $userLender;

    private static FinancingOrder $order;

    private static FinancingOrder $otherOrder;

    private static OtpifyCode $otpifyCode;

    private static OtpifyCode $otherOtpifyCode;

    private static TraderOrder $traderOrder;

    public function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);

        self::$order = $this->createOrder(self::$company->id, self::$userLender->id, [
            'national_id' => '2553451234',
        ]);
        self::$traderOrder = self::$order->traderOrders()->create([
            'provider' => 'dmcc',
            'status' => TraderOrderStatus::InProgress,
            'reference' => 123,
        ]);

        self::$otherOrder = $this->createOrder(self::$company->id, self::$userLender->id, [
            'national_id' => '1591192305',
        ]);
        self::$traderOrder = self::$order->traderOrders()->create([
            'provider' => 'fake',
            'reference' => '123456789',
            'status' => TraderOrderStatus::InProgress,
        ]);

        self::$otpifyCode = Otpify::driver(config('otpify.default_ni_driver'))->send(request(), self::$order);
        self::$otherOtpifyCode = Otpify::driver(config('otpify.default_ni_driver'))->send(request(), self::$otherOrder);
    }

    public function test_verify_otp_client_wakala_success()
    {
        $this->postJson('api/v1/client/wakala/verify', [
            'national_id' => self::$order->national_id,
            'order_id' => self::$order->id,
            'vid' => self::$otpifyCode->getVid(),
            'code' => '2023',
        ])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => ['token', 'template'],
            ]);
    }

    public function test_verify_otp_client_wakala_with_invalid_national_id_unsuccessful()
    {
        $this->postJson('api/v1/client/wakala/verify', [
            'national_id' => '1591192305',
            'order_id' => self::$order->id,
            'vid' => self::$otpifyCode->getVid(),
            'code' => '2023',
        ])
            ->assertNotFound();
    }

    public function test_verify_otp_client_wakala_with_already_verified_order_unsuccessful()
    {
        self::$traderOrder->traderHistories()->create([
            'action' => FinancingOrderHistory::ClientWakalaAccepted,
        ]);

        $this->postJson('api/v1/client/wakala/verify', [
            'national_id' => self::$order->national_id,
            'order_id' => self::$order->id,
            'vid' => self::$otpifyCode->getVid(),
            'code' => '2023',
        ])
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function test_verify_otp_client_wakala_with_vid_not_for_order_id_unsuccessful()
    {
        $this->postJson('api/v1/client/wakala/verify', [
            'national_id' => self::$otherOrder->national_id,
            'order_id' => self::$order->id,
            'vid' => self::$otherOtpifyCode->getVid(),
            'code' => '2023',
        ])
            ->assertNotFound();
    }

    public function test_verify_otp_client_wakala_with_vid_not_for_order_national_id_unsuccessful()
    {
        $this->postJson('api/v1/client/wakala/verify', [
            'national_id' => self::$order->national_id,
            'order_id' => self::$order->id,
            'vid' => self::$otherOtpifyCode->getVid(),
            'code' => '2023',
        ])
            ->assertUnauthorized();
    }

    public function test_verify_otp_client_wakala_with_vid_expired_unsuccessful()
    {
        self::$otpifyCode->update(['expiration_date' => now()->subDay()]);
        $this->postJson('api/v1/client/wakala/verify', [
            'national_id' => self::$order->national_id,
            'order_id' => self::$order->id,
            'vid' => self::$otpifyCode->getVid(),
            'code' => '2023',
        ])
            ->assertUnauthorized();
    }

    public function test_verify_otp_client_wakala_with_vid_already_used_unsuccessful()
    {
        $this->postJson('api/v1/client/wakala/verify', [
            'national_id' => self::$order->national_id,
            'order_id' => self::$order->id,
            'vid' => self::$otpifyCode->getVid(),
            'code' => '2023',
        ])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => ['token', 'template'],
            ]);

        $this->postJson('api/v1/client/wakala/verify', [
            'national_id' => self::$order->national_id,
            'order_id' => self::$order->id,
            'vid' => self::$otpifyCode->getVid(),
            'code' => '2023',
        ])
            ->assertStatus(Response::HTTP_BAD_REQUEST);
    }
}
