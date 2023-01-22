<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\FinancingOrders;

use App\Enums\ErrorCode;
use App\Enums\Role;
use App\Enums\WalletType;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Models\Wallet;
use App\Settings\Classes\ProjectSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithSettings;
use Tests\Traits\InteractsWithUser;

class CreateOrderWithoutVerificationControllerTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithUser;
    use InteractsWithCompany;
    use InteractsWithSettings;

    private static Company $company;

    private static Company $companyWithEmptyWallet;

    private static Wallet $wallet;

    private static Wallet $emptyWallet;

    private static User $userLenderAdmin;

    private static User $LenderApiUser;

    private static User $userLenderSupervisor;

    private static User $userLenderAdminBelongsToCompanyHasEmptyWallet;

    private static User $userLenderAdminWithoutEmailVerification;

    private static User $userLenderOrderCreator;

    private static User $userLenderBilling;

    private static array $orderDetails;

    private static array $orderDetailsWithInvalidNationalID;

    private static FinancingOrder $financingOrder;

    private static FinancingOrder $pendingApprovalOrder;

    private static $projectSettings;

    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompany('2000', ['order_cost' => 200]);
        [self::$companyWithEmptyWallet, self::$emptyWallet] = $this->createCompany('0', ['company_cr' => '12345678911']);

        self::$projectSettings = $this->app->make(ProjectSettings::class);
        self::$userLenderAdminBelongsToCompanyHasEmptyWallet = $this->createLenderUser(self::$companyWithEmptyWallet->id, Role::LenderAdmin);
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$LenderApiUser = $this->createLenderUser(self::$company->id, Role::LenderApiUser);
        self::$userLenderOrderCreator = $this->createLenderUser(self::$company->id, Role::LenderOrderCreator);
        self::$userLenderSupervisor = $this->createLenderUser(self::$company->id, Role::LenderSupervisor);
        self::$userLenderBilling = $this->createLenderUser(self::$company->id, Role::LenderBilling);
        self::$userLenderAdminWithoutEmailVerification = $this->createLenderUser(
            self::$company->id,
            Role::LenderAdmin,
            [
                'email_verified_at' => null,
            ]
        );

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

    public function test_create_order_without_verification_does_not_work_if_wallet_is_empty()
    {
        $this->actingAs(self::$userLenderAdminBelongsToCompanyHasEmptyWallet)
            ->postJson(
                '/api/v1/lender/orders/no-verification',
                self::$orderDetails,
                ['X-Company' => self::$companyWithEmptyWallet->id]
            )
            ->assertStatus(Response::HTTP_BAD_REQUEST)->assertJsonFragment([
                'message' => trans('error.no_enough_balance'),
                'code' => ErrorCode::BALANCE_NOT_ENOUGH,
            ]);
    }

    public function test_create_order_without_verification_does_not_work_when_national_id_is_not_valid()
    {
        $this->actingAs(self::$userLenderAdmin)
            ->postJson(
                '/api/v1/lender/orders/no-verification',
                self::$orderDetailsWithInvalidNationalID,
                ['X-Company' => self::$company->id]
            )
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_create_order_without_verification_does_not_work_when_phone_number_is_not_valid()
    {
        $this->actingAs(self::$userLenderAdmin)
            ->postJson(
                '/api/v1/lender/orders/no-verification',
                array_merge(self::$orderDetails, ['phone_number' => '01010420399']),
                ['X-Company' => self::$company->id]
            )
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_create_order_without_verification_does_not_work_if_amount_is_greater_than_selling_price()
    {
        $amount = 10;
        $sellingPrice = 9;

        $this->actingAs(self::$userLenderAdmin)
            ->postJson(
                '/api/v1/lender/orders/no-verification',
                array_merge(self::$orderDetails, ['amount' => $amount, 'selling_price' => $sellingPrice]),
                ['X-Company' => self::$company->id]
            )
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_create_order_without_verification_assert_balance_after_creating_order()
    {
        $wallet = self::$company->balance(WalletType::CompanyWallet);

        $response = $this->actingAs(self::$userLenderAdmin)
            ->postJson(
                '/api/v1/lender/orders/no-verification',
                self::$orderDetails,
                ['X-Company' => self::$company->id]
            );
        $walletAfterCreation = self::$company->balance(WalletType::CompanyWallet);
        $orderCost = self::$company->order_cost->getMoney();
        $vatRate = self::$projectSettings->vat_rate;

        $financingOrder = $response->getOriginalContent()->data;
        $vatPercentageFee = money($financingOrder->amount * $vatRate)->getMoney();

        $this->assertTrue($wallet->subtract($orderCost->add($vatPercentageFee))->equals($walletAfterCreation));
    }

    public function test_create_order_without_verification_lender_user_can_not_ceate_order_without_email_verification()
    {
        $this->actingAs(self::$userLenderAdminWithoutEmailVerification)
            ->postJson(
                '/api/v1/lender/orders/no-verification',
                self::$orderDetails,
                ['X-Company' => self::$company->id]
            )
            ->assertStatus(403)->assertJsonFragment([
                'message' => __('error.must_verify_email'),
                'code' => ErrorCode::EMAIL_NOT_VERIFIED,
            ]);
    }

    public function test_create_order_without_verification_lender_api_user_can_create_order()
    {
        $this->actingAs(self::$LenderApiUser)
            ->postJson(
                '/api/v1/lender/orders/no-verification',
                self::$orderDetails,
                ['X-Company' => self::$company->id]
            )
            ->assertStatus(200);
    }

    public function test_create_order_without_verification_lender_admin_can_create_order()
    {
        $this->actingAs(self::$userLenderAdmin)
            ->postJson(
                '/api/v1/lender/orders/no-verification',
                self::$orderDetails,
                ['X-Company' => self::$company->id]
            )
            ->assertStatus(200);
    }

    public function test_create_order_without_verification_order_creator_user_can_create_order()
    {
        $this->actingAs(self::$userLenderOrderCreator)
            ->postJson(
                '/api/v1/lender/orders/no-verification',
                self::$orderDetails,
                ['X-Company' => self::$company->id]
            )
            ->assertStatus(200);
    }

    public function test_create_order_without_verification_supervisor_user_can_create_order()
    {
        $this->actingAs(self::$userLenderSupervisor)
            ->postJson(
                '/api/v1/lender/orders/no-verification',
                self::$orderDetails,
                ['X-Company' => self::$company->id]
            )
            ->assertStatus(200);
    }

    public function test_create_order_without_verification_billing_user_can_not_create_order()
    {
        $this->actingAs(self::$userLenderBilling)
            ->postJson(
                '/api/v1/lender/orders/no-verification',
                self::$orderDetails,
                ['X-Company' => self::$company->id]
            )
            ->assertStatus(403);
    }
}
