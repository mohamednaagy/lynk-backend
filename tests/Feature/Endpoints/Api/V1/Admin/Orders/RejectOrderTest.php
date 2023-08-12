<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Orders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\CompanyStatus;
use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class RejectOrderTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithUser;
    use InteractsWithCompany;

    private static Company $company;

    private static Company $notApprovedCompany;

    private static Wallet $wallet;

    private static Wallet $notApprovedCompanyWallet;

    private static Wallet $secondWallet;

    private static User $userSuperAdmin;

    private static User $userLenderAdmin;

    private static User $notApprovedCompanyLenderAdmin;

    private static array $orderDetails;

    private static FinancingOrder $unrejectableOrder;

    private static FinancingOrder $rejectedOrder;

    private static FinancingOrder $pendingApprovalOrder;

    private static string $statusReason;

    private static string $apiUrlUnRejectedOrder;

    private static string $apiUrlRejectedOrder;

    private static string $apiUrlPendingApprovalOrder;

    private static string $apiUrlOrder;

    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompany('2000');
        [self::$notApprovedCompany, self::$notApprovedCompanyWallet] = $this->createCompany(
            '2000',
            [
                'status' => CompanyStatus::Pending,
                'company_cr' => '12345678977',
            ]
        );

        self::$notApprovedCompanyLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$userSuperAdmin = $this->createSuperAdminUser();
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin);

        self::$statusReason = Str::random(80);

        self::$unrejectableOrder = $this->createOrder(self::$company->id, self::$userLenderAdmin->id, ['status' => FinancingOrderStatus::Approved]);
        self::$pendingApprovalOrder = $this->createOrder(self::$company->id, self::$userLenderAdmin->id, ['status' => FinancingOrderStatus::PendingApproval]);

        self::$rejectedOrder = $this->createOrder(self::$company->id, self::$userLenderAdmin->id, [
            'status' => FinancingOrderStatus::Rejected,
            'status_reason' => self::$statusReason,
        ]);

        self::$apiUrlUnRejectedOrder = '/api/v1/admin/orders/'.self::$unrejectableOrder->id.'/reject';
        self::$apiUrlRejectedOrder = '/api/v1/admin/orders/'.self::$rejectedOrder->id.'/reject';
        self::$apiUrlPendingApprovalOrder = '/api/v1/admin/orders/'.self::$pendingApprovalOrder->id.'/reject';
        self::$apiUrlOrder = '/api/v1/admin/orders/'.self::$pendingApprovalOrder->id;
    }

    public function test_reject_order_controller_order_can_not_moved_to_reject_status()
    {
        $this->actingAs(self::$userSuperAdmin)
            ->putJson(
                self::$apiUrlUnRejectedOrder,
                ['status_reason' => self::$statusReason],
            )
            ->assertStatus(400)
            ->assertJsonFragment([
                'message' => __('error.order_cannot_be_approved_because_it_is_approved'),
            ]);
    }

    public function test_reject_order_controller_reject_order_that_does_not_exists()
    {
        $this->actingAs(self::$userSuperAdmin)
            ->putJson(
                '/api/v1/admin/orders/'. 400 .'/reject',
                ['status_reason' => self::$statusReason],
            )
            ->assertStatus(404);
    }

    public function test_reject_order_controller_reject_order_that_is_already_rejected()
    {
        $this->actingAs(self::$userSuperAdmin)
            ->putJson(
                self::$apiUrlRejectedOrder,
                ['status_reason' => self::$statusReason],
            )
            ->assertJsonFragment([
                'message' => __('error.order_cannot_be_approved_because_it_is_approved'),
            ]);
    }

    public function test_reject_order_controller_manger_admin_without_permission_failed()
    {
        Grantify::syncRoleToModel(self::$userSuperAdmin, Role::Manager);

        $this->actingAs(self::$userSuperAdmin)
            ->putJson(
                self::$apiUrlPendingApprovalOrder,
                ['status_reason' => self::$statusReason],
            )
            ->assertStatus(403);
    }

    public function test_reject_order_controller_manger_admin_with_permission()
    {
        Grantify::syncRoleToModel(self::$userSuperAdmin, Role::Manager);
        Grantify::assignPermissionToModel(self::$userSuperAdmin, perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Reject]));

        $this->actingAs(self::$userSuperAdmin)
            ->putJson(
                '/api/v1/admin/orders/'.self::$pendingApprovalOrder->id.'/reject',
                ['status_reason' => self::$statusReason],
            )
            ->assertStatus(200);
    }

    public function test_reject_order_controller_lender_admin_reject_order_successfully_and_check_status_reason_message()
    {
        $statusReason = 'issue with the company';
        $this->actingAs(self::$userSuperAdmin)
            ->putJson(
                self::$apiUrlPendingApprovalOrder,
                ['status_reason' => $statusReason],
            )
            ->assertStatus(200);

        $this->getJson(
            self::$apiUrlOrder,
        )
            ->assertJsonFragment(['status_reason' => $statusReason]);
    }
}
