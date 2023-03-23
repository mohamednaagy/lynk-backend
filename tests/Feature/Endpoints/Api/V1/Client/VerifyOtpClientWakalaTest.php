<?php

namespace Endpoints\Api\V1\Client;

use App\Enums\FinancingOrderHistory;
use App\Enums\Role;
use App\Models\Company;
use App\Models\TraderOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Otpify\Facades\Otpify;
use Modules\Otpify\Models\OtpifyCode;
use Symfony\Component\HttpFoundation\Response;
use Tests\Support\FinancingOrders\CommittedOrder;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\Support\FinancingOrders\TraderOrderScenario;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class VerifyOtpClientWakalaTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany;

    private static Company $company;

    private static User $userLender;

    private static CommittedOrder $order;

    private static CommittedOrder $otherOrder;

    private static OtpifyCode $otpifyCode;

    private static OtpifyCode $otherOtpifyCode;

    private static TraderOrder|Model $traderOrder;

    private static TraderOrder|Model $otherTraderOrder;

    public function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);

        self::$order = OrderScenario::inProgress()
            ->nationalId('2553451234')
            ->lender(self::$company)
            ->creator(self::$userLender)
            ->commit();

        self::$traderOrder = InProgressOrder::of(self::$order)->createTraderOrder('dmcc');

        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::WaitingClientWakala);

        self::$otherOrder = OrderScenario::inProgress()
            ->nationalId('1591192305')
            ->lender(self::$company)
            ->creator(self::$userLender)
            ->commit();

        self::$otherTraderOrder = InProgressOrder::of(self::$otherOrder)->createTraderOrder('fake');

        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::WaitingClientWakala);

        self::$otpifyCode = Otpify::driver(config('otpify.default_ni_driver'))->send(request(), self::$order->model());
        self::$otherOtpifyCode = Otpify::driver(config('otpify.default_ni_driver'))->send(request(), self::$otherOrder->model());
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
        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::ClientWakalaAccepted);

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
