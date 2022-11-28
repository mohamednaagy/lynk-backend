<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\FinancingOrders;

use App\Enums\ErrorCode;
use App\Enums\Role;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
use Bavix\Wallet\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class CreateOrderWithoutVerificationControllerTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithLender;

    private static Company $company;

    private static Company $companyWithEmptyWallet;

    private static Wallet $wallet;

    private static Wallet $emptyWallet;

    private static User $userLenderAdmin;

    private static User $userLenderAdminBelongsToCompanyHasEmptyWallet;

    private static array $orderDetails;

    private static array $orderDetailsWithInvalidNationalID;

    private static FinancingOrder $financingOrder;

    private static FinancingOrder $pendingApprovalOrder;

    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompany('2000');
        [self::$companyWithEmptyWallet, self::$emptyWallet] = $this->createCompany('0', ['company_cr' => '12345678911']);

        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'lenderAdmin@bim.com');
        self::$userLenderAdminBelongsToCompanyHasEmptyWallet = $this->createLenderUser(self::$companyWithEmptyWallet->id, Role::LenderAdmin, 'lenderAdmin2@bim.com');

        self::$orderDetails = [
            'national_id' => '2553451234',
            'amount' => 1000,
            'selling_price' => 1000.5,
            'phone_country_code' => 'SA',
            'phone_number' => '503811915',
        ];

        self::$orderDetailsWithInvalidNationalID = [
            'national_id' => '25513451234',
            'amount' => 1000,
            'selling_price' => 1000.5,
            'phone_country_code' => 'SA',
            'phone_number' => '503811915',
        ];
    }

    public function test_wallet_is_empty()
    {
        $this->actingAs(self::$userLenderAdminBelongsToCompanyHasEmptyWallet);

        $response = $this->postJson(
            '/api/v1/lender/orders/no-verification',
            self::$orderDetails,
            ['X-Company' => self::$companyWithEmptyWallet->id]
        );

        $response->assertStatus(Response::HTTP_BAD_REQUEST)->assertJsonFragment([
            'message' => trans('error.no_enough_balance'),
            'code' => ErrorCode::BALANCE_NOT_ENOUGH,
        ]);
    }

    public function test_national_id_is_not_valid()
    {
        $this->actingAs(self::$userLenderAdmin);
        $response = $this->postJson(
            '/api/v1/lender/orders/no-verification',
            self::$orderDetailsWithInvalidNationalID,
            ['X-Company' => self::$company->id]
        );

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_phone_number_is_not_valid()
    {
        $this->actingAs(self::$userLenderAdmin);
        $response = $this->postJson(
            '/api/v1/lender/orders/no-verification',
            array_merge(self::$orderDetails, ['phone_number' => '01010420399']),
            ['X-Company' => self::$company->id]
        );

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_amount_can_not_be_more_than_selling_price()
    {
        $amount = 10;
        $sellingPrice = 9;
        $this->actingAs(self::$userLenderAdmin);
        $response = $this->postJson(
            '/api/v1/lender/orders/no-verification',
            array_merge(self::$orderDetails, ['amount' => $amount, 'selling_price' => $sellingPrice]),
            ['X-Company' => self::$company->id]
        );

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_order_created_successfully()
    {
        $this->actingAs(self::$userLenderAdmin);
        $response = $this->postJson(
            '/api/v1/lender/orders/no-verification',
            self::$orderDetails,
            ['X-Company' => self::$company->id]
        );

        $response->assertStatus(Response::HTTP_OK);
    }
}
