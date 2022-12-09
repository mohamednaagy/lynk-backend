<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\Wallet;

use App\Enums\CompanyStatus;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class LenderWalletTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    private static Company $company;

    private static Company $pendingCompany;

    private static Company $underReviewCompany;

    private static Company $rejectedCompany;

    private static Company $approvedCompany;

    private static User $userLenderAdmin;

    private static User $userLenderAdminWithoutVerifiedEmail;

    private static User $userLenderSupervisor;

    private static User $userLenderBilling;

    private static User $userLenderApi;

    private static User $userLenderOrderCreator;

    private static Wallet $wallet;

    private static Wallet $pendingWallet;

    private static Wallet $underReviewWallet;

    private static Wallet $rejectedWallet;

    private static Wallet $approvedWallet;

    /**
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910', 'order_cost' => 200, 00]);
        [self::$pendingCompany, self::$pendingWallet] = $this->createCompany('2000', ['company_cr' => '12345678911', 'status' => CompanyStatus::Pending()->value]);
        [self::$underReviewCompany, self::$underReviewWallet] = $this->createCompany('2000', ['company_cr' => '12345678912', 'status' => CompanyStatus::UnderReview()->value]);
        [self::$rejectedCompany, self::$rejectedWallet] = $this->createCompany('2000', ['company_cr' => '12345678913', 'status' => CompanyStatus::Rejected()->value]);
        [self::$approvedCompany, self::$approvedWallet] = $this->createCompany('2000', ['company_cr' => '12345678914', 'status' => CompanyStatus::Approved()->value]);
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'lenderAdmin@bim.com');
        self::$userLenderAdminWithoutVerifiedEmail = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'lenderAdmin@bim.com', ['email_verified_at' => null]);
        self::$userLenderSupervisor = $this->createLenderUser(self::$company->id, Role::LenderSupervisor, 'lenderSupervisor@bim.com');
        self::$userLenderBilling = $this->createLenderUser(self::$company->id, Role::LenderBilling, 'lenderBilling@bim.com');
        self::$userLenderApi = $this->createLenderUser(self::$company->id, Role::LenderApiUser, 'lenderApi@bim.com');
        self::$userLenderOrderCreator = $this->createLenderUser(self::$company->id, Role::LenderOrderCreator, 'lenderOrderCreator@bim.com');
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_get_wallet_balance(): void
    {
        $this->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/wallet/balance')
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_user_can_get_wallet_balance(): void
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/wallet/balance')
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'available_orders' => '10',
                    'balance' => '20.00',
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_supervisor_user_can_get_wallet_balance(): void
    {
        $this->actingAs(self::$userLenderSupervisor)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/wallet/balance')
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'available_orders' => '10',
                    'balance' => '20.00',
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_billing_user_can_get_wallet_balance(): void
    {
        $this->actingAs(self::$userLenderBilling)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/wallet/balance')
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'available_orders' => '10',
                    'balance' => '20.00',
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_api_user_can_get_wallet_balance(): void
    {
        $res = $this->actingAs(self::$userLenderApi)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/wallet/balance')
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'available_orders' => '10',
                    'balance' => '20.00',
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_order_creator_user_cant_get_wallet_balance(): void
    {
        $this->actingAs(self::$userLenderOrderCreator)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/wallet/balance')
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_that_admin_user_cant_get_wallet_balance_with_pending_company(): void
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$pendingCompany->id)
            ->getJson('api/v1/lender/wallet/balance')
            ->assertForbidden()
            ->assertExactJson([
                'code' => 1015,
                'message' => __('The company is not active'),
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_user_cant_get_wallet_balance_with_under_review_company(): void
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$underReviewCompany->id)
            ->getJson('api/v1/lender/wallet/balance')
            ->assertForbidden()
            ->assertExactJson([
                'code' => 1015,
                'message' => __('The company is not active'),
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_user_cant_get_wallet_balance_with_rejected_company(): void
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$rejectedCompany->id)
            ->getJson('api/v1/lender/wallet/balance')
            ->assertForbidden()
            ->assertExactJson([
                'code' => 1015,
                'message' => __('The company is not active'),
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_user_without_verified_email_cant_get_wallet_balance_with_under_review_company(): void
    {
        $this->actingAs(self::$userLenderAdminWithoutVerifiedEmail)
            ->withHeader('X-Company', self::$underReviewCompany->id)
            ->getJson('api/v1/lender/wallet/balance')
            ->assertForbidden()
            ->assertExactJson([
                'code' => 1008,
                'message' => __('You must verify your email address'),
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_user_without_verified_email_cant_get_wallet_balance_with_approved_company(): void
    {
        $this->actingAs(self::$userLenderAdminWithoutVerifiedEmail)
            ->withHeader('X-Company', self::$approvedCompany->id)
            ->getJson('api/v1/lender/wallet/balance')
            ->assertForbidden()
            ->assertExactJson([
                'code' => 1008,
                'message' => __('You must verify your email address'),
            ]);
    }
}
