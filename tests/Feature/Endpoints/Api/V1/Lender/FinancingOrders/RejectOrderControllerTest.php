<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\FinancingOrders;

use App\Enums\CompanyStatus;
use App\Enums\ErrorCode;
use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class RejectOrderControllerTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithLender;

    private static Company $company;

    private static Company $secondCompany;

    private static Company $notApprovedCompany;

    private static Wallet $wallet;

    private static Wallet $notApprovedCompanyWallet;

    private static Wallet $secondWallet;

    private static User $userLenderAdmin;

    private static User $userLenderBilling;

    private static User $userLendersupervisor;

    private static User $userLenderCreator;

    private static User $userLenderAdminWithoutEmailVerification;

    private static User $notApprovedCompanyLenderAdmin;

    private static array $orderDetails;

    private static FinancingOrder $unrejectableOrder;

    private static FinancingOrder $rejectedOrder;

    private static FinancingOrder $pendingApprovalOrder;

    private static string $statusReason;

    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompany('2000');
        [self::$secondCompany, self::$secondWallet] = $this->createCompany('3000', ['company_cr' => '12345678911']);
        [self::$notApprovedCompany, self::$notApprovedCompanyWallet] = $this->createCompany(
            '2000',
            [
                'status' => CompanyStatus::Pending,
                'company_cr' => '12345678977',
            ]
        );

        self::$notApprovedCompanyLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'notApprovedCompanyLenderAdmin@bim.com');
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'lenderAdmin@bim.com');
        self::$userLendersupervisor = $this->createLenderUser(self::$company->id, Role::LenderSupervisor, 'supervisor@bim.com');
        self::$userLenderBilling = $this->createLenderUser(self::$company->id, Role::LenderBilling, 'userLenderBilling@bim.com');
        self::$userLenderCreator = $this->createLenderUser(self::$company->id, Role::LenderOrderCreator, 'LenderOrderCreator@bim.com');
        self::$userLenderAdminWithoutEmailVerification = $this->createLenderUser(
            self::$company->id,
            Role::LenderAdmin,
            'userLenderAdminWithoutEmailVerification@bim.com',
            ['email_verified_at' => null]
        );
        self::$statusReason = Str::random(80);

        self::$unrejectableOrder = $this->createOrder(self::$company->id, self::$userLenderAdmin->id, ['status' => FinancingOrderStatus::ContractSigned]);
        self::$pendingApprovalOrder = $this->createOrder(self::$company->id, self::$userLenderAdmin->id, ['status' => FinancingOrderStatus::PendingApproval]);

        self::$rejectedOrder = $this->createOrder(self::$company->id, self::$userLenderAdmin->id, [
            'status' => FinancingOrderStatus::Rejected,
            'status_reason' => self::$statusReason,
        ]);
    }

    public function test_reject_order_controller_order_can_not_moved_to_reject_status()
    {
        $this->actingAs(self::$userLenderAdmin)
            ->putJson(
                '/api/v1/lender/orders/'.self::$unrejectableOrder->id.'/reject',
                ['status_reason' => self::$statusReason],
                ['X-Company' => self::$company->id]
            )
            ->assertStatus(400)
            ->assertJsonFragment([
                'message' => __('error.order_cannot_be_approved_because_it_is_approved'),
            ]);
    }

    public function test_reject_order_controller_other_company_can_not_reject_order()
    {
        $this->actingAs(self::$userLenderAdmin)
            ->putJson(
                '/api/v1/lender/orders/'.self::$unrejectableOrder->id.'/reject',
                ['status_reason' => self::$statusReason],
                ['X-Company' => self::$secondCompany->id]
            )
            ->assertStatus(404);
    }

    public function test_reject_order_controller_reject_order_that_does_not_exists()
    {
        $this->actingAs(self::$userLenderAdmin)
            ->putJson(
                '/api/v1/lender/orders/'. 400 .'/reject',
                ['status_reason' => self::$statusReason],
                ['X-Company' => self::$company->id]
            )
            ->assertStatus(404);
    }

    public function test_reject_order_controller_reject_order_that_is_already_rejected()
    {
        $this->actingAs(self::$userLenderAdmin)
            ->putJson(
                '/api/v1/lender/orders/'.self::$rejectedOrder->id.'/reject',
                ['status_reason' => self::$statusReason],
                ['X-Company' => self::$company->id]
            )
            ->assertJsonFragment([
                'message' => __('error.order_cannot_be_approved_because_it_is_approved'),
            ]);
    }

    public function ttest_reject_order_controller_can_not_reject_order_without_email_verification()
    {
        $this->actingAs(self::$userLenderAdminWithoutEmailVerification)
            ->putJson(
                '/api/v1/lender/orders/'.self::$unrejectableOrder->id.'/reject',
                [],
                ['X-Company' => self::$company->id]
            )
            ->assertStatus(403)
            ->assertJsonFragment([
                'message' => __('error.must_verify_email'),
                'code' => ErrorCode::EMAIL_NOT_VERIFIED,
            ]);
    }

    public function test_reject_order_controller_lender_order_billing_can_not_reject_order()
    {
        $this->actingAs(self::$userLenderBilling)
            ->putJson(
                '/api/v1/lender/orders/'.self::$pendingApprovalOrder->id.'/reject',
                ['status_reason' => self::$statusReason],
                ['X-Company' => self::$company->id]
            )
            ->assertStatus(403);
    }

    public function test_reject_order_controller_lender_order_creator_can_not_reject_order()
    {
        $this->actingAs(self::$userLenderCreator)
            ->putJson(
                '/api/v1/lender/orders/'.self::$pendingApprovalOrder->id.'/reject',
                ['status_reason' => self::$statusReason],
                ['X-Company' => self::$company->id]
            )
            ->assertStatus(403);
    }

    public function test_reject_order_controller_lender_order_supervisor_can_reject_order()
    {
        $this->actingAs(self::$userLendersupervisor)
            ->putJson(
                '/api/v1/lender/orders/'.self::$pendingApprovalOrder->id.'/reject',
                ['status_reason' => self::$statusReason],
                ['X-Company' => self::$company->id]
            )
            ->assertStatus(200);
    }

    public function test_reject_order_controller_lender_admin_reject_order_successfully_and_check_status_reason_message()
    {
        $statusReason = 'issue with the company';
        $this->actingAs(self::$userLenderAdmin)
            ->putJson(
                '/api/v1/lender/orders/'.self::$pendingApprovalOrder->id.'/reject',
                ['status_reason' => $statusReason],
                ['X-Company' => self::$company->id]
            )
            ->assertStatus(200);

        $this->getJson(
            '/api/v1/lender/orders/'.self::$pendingApprovalOrder->id,
            ['X-Company' => self::$company->id]
        )
            ->assertJsonFragment(['status_reason' => $statusReason]);
    }

    public function test_reject_order_controller_reject_order_will_not_work_when_company_not_active()
    {
        $this->actingAs(self::$notApprovedCompanyLenderAdmin)
            ->putJson(
                '/api/v1/lender/orders/'.self::$pendingApprovalOrder->id.'/reject',
                ['status_reason' => self::$statusReason],
                ['X-Company' => self::$notApprovedCompany->id]
            )
            ->assertStatus(403)->assertJsonFragment([
            'message' => __('error.company_not_active'),
            'code' => ErrorCode::COMPANY_NOT_ACTIVE,
        ]);
    }
}
