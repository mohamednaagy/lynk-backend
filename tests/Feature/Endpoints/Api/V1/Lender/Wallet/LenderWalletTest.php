<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\Wallet;

use App\Enums\CompanyStatus;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use App\Settings\Classes\ProjectSettings;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class LenderWalletTest extends TestCase
{
    use InteractsWithCompany, InteractsWithUser, RefreshDatabase;

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

    private static string $endpoint;

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$company = $this->createLenderCompanyWithStandardOrderCost('1150000000', ['company_cr' => '12345678910', 'order_cost' => 200]);
        [self::$pendingCompany, self::$pendingWallet] = $this->createCompany('11500000', ['company_cr' => '12345678911', 'status' => CompanyStatus::Pending()->value]);
        [self::$underReviewCompany, self::$underReviewWallet] = $this->createCompany('2000', ['company_cr' => '12345678912', 'status' => CompanyStatus::UnderReview()->value]);
        [self::$rejectedCompany, self::$rejectedWallet] = $this->createCompany('2000', ['company_cr' => '12345678913', 'status' => CompanyStatus::Rejected()->value]);
        [self::$approvedCompany, self::$approvedWallet] = $this->createCompany('2000', ['company_cr' => '12345678914', 'status' => CompanyStatus::Approved()->value]);
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$userLenderAdminWithoutVerifiedEmail = $this->createLenderUser(self::$company->id, Role::LenderAdmin, ['email_verified_at' => null]);
        self::$userLenderSupervisor = $this->createLenderUser(self::$company->id, Role::LenderSupervisor);
        self::$userLenderBilling = $this->createLenderUser(self::$company->id, Role::LenderBilling);
        self::$userLenderApi = $this->createLenderUser(self::$company->id, Role::LenderApiUser);
        self::$userLenderOrderCreator = $this->createLenderUser(self::$company->id, Role::LenderOrderCreator);

        $projectSettings = app(ProjectSettings::class);
        $projectSettings->vat_rate = 0.15;
        $projectSettings->save();
        self::$endpoint = 'api/v1/lender/wallet/balance';
    }

    public function test_un_auth_user_cant_get_wallet_balance(): void
    {
        $this->withHeader('X-Company', self::$company->id)
            ->getJson(self::$endpoint)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_admin_user_can_get_wallet_balance_successfully(): void
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->getJson(self::$endpoint)
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'available_orders' => '1000',
                    'available_orders_formatted' => '1,000',
                    'balance' => '115000.00',
                    'balance_formatted' => '115,000.00',
                ],
            ]);
    }

    public function test_lender_supervisor_user_can_get_wallet_balance_successfully(): void
    {
        $this->actingAs(self::$userLenderSupervisor)
            ->withHeader('X-Company', self::$company->id)
            ->getJson(self::$endpoint)
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'available_orders' => '1000',
                    'available_orders_formatted' => '1,000',
                    'balance' => '115000.00',
                    'balance_formatted' => '115,000.00',
                ],
            ]);
    }

    public function test_lender_billing_user_can_get_wallet_balance_successfully(): void
    {
        $this->actingAs(self::$userLenderBilling)
            ->withHeader('X-Company', self::$company->id)
            ->getJson(self::$endpoint)
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'available_orders' => '1000',
                    'available_orders_formatted' => '1,000',
                    'balance' => '115000.00',
                    'balance_formatted' => '115,000.00',
                ],
            ]);
    }

    public function test_lender_api_user_can_get_wallet_balance_successfully(): void
    {
        $res = $this->actingAs(self::$userLenderApi)
            ->withHeader('X-Company', self::$company->id)
            ->getJson(self::$endpoint)
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'available_orders' => '1000',
                    'available_orders_formatted' => '1,000',
                    'balance' => '115000.00',
                    'balance_formatted' => '115,000.00',
                ],
            ]);
    }

    public function test_lender_order_creator_user_cant_get_wallet_balance(): void
    {
        $this->actingAs(self::$userLenderOrderCreator)
            ->withHeader('X-Company', self::$company->id)
            ->getJson(self::$endpoint)
            ->assertForbidden();
    }

    public function test_lender_admin_user_cant_get_wallet_balance_with_pending_company(): void
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$pendingCompany->id)
            ->getJson(self::$endpoint)
            ->assertForbidden()
            ->assertExactJson([
                'code' => 1015,
                'message' => __('error.company_not_active'),
            ]);
    }

    public function test_lender_admin_user_cant_get_wallet_balance_with_under_review_company(): void
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$underReviewCompany->id)
            ->getJson(self::$endpoint)
            ->assertForbidden()
            ->assertExactJson([
                'code' => 1015,
                'message' => __('error.company_not_active'),
            ]);
    }

    public function test_lender_admin_user_cant_get_wallet_balance_with_rejected_company(): void
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$rejectedCompany->id)
            ->getJson(self::$endpoint)
            ->assertForbidden()
            ->assertExactJson([
                'code' => 1015,
                'message' => __('error.company_not_active'),
            ]);
    }

    public function test_lender_admin_user_without_verified_email_cant_get_wallet_balance_with_under_review_company(): void
    {
        $this->actingAs(self::$userLenderAdminWithoutVerifiedEmail)
            ->withHeader('X-Company', self::$underReviewCompany->id)
            ->getJson(self::$endpoint)
            ->assertForbidden()
            ->assertExactJson([
                'code' => 1008,
                'message' => __('error.must_verify_email'),
            ]);
    }

    public function test_lender_admin_user_without_verified_email_cant_get_wallet_balance_with_approved_company(): void
    {
        $this->actingAs(self::$userLenderAdminWithoutVerifiedEmail)
            ->withHeader('X-Company', self::$approvedCompany->id)
            ->getJson(self::$endpoint)
            ->assertForbidden()
            ->assertExactJson([
                'code' => 1008,
                'message' => __('error.must_verify_email'),
            ]);
    }
}
