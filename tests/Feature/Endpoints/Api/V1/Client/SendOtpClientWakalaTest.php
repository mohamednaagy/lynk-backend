<?php

namespace Endpoints\Api\V1\Client;

use App\Enums\Role;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class SendOtpClientWakalaTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    private static Company $company;

    private static User $userLender;

    private static FinancingOrder $order;

    public function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'lenderAdmin@bim.com');
        self::$order = $this->createOrder(self::$company->id, self::$userLender->id, [
            'national_id' => '2553451234',
        ]);
    }

    public function test_send_otp_client_wakala_success()
    {
        $this->postJson('api/v1/client/wakala/access', [
            'national_id' => self::$order->national_id,
            'order_id' => self::$order->id,
        ])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => ['vid'],
            ]);
    }

    public function test_send_otp_client_wakala_with_invalid_national_id_unsuccessful()
    {
        $this->postJson('api/v1/client/wakala/access', [
            'national_id' => '1591192305',
            'order_id' => self::$order->id,
        ])
            ->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public function test_send_otp_client_wakala_with_already_verified_order_unsuccessful()
    {
        self::$order->update(['client_wakala_accepted_at' => now()]);

        $this->postJson('api/v1/client/wakala/access', [
            'national_id' => '2553451234',
            'order_id' => self::$order->id,
        ])
            ->assertStatus(Response::HTTP_BAD_REQUEST);
    }
}
