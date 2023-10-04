<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Orders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class ApproveOrderTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany;

    private static Company $company;

    private static User $userAdmin;

    private static User $userLender;

    private static Builder|Model $financingOrder;

    private static string $apiUrl;

    public function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany('2000', ['company_cr' => '1234567891']);

        self::$userAdmin = $this->createSuperAdminUser(Role::Admin);
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$financingOrder = $this->createOrder(self::$company->id, self::$userLender->id, ['status' => FinancingOrderStatus::PendingApproval]);
        self::$apiUrl = 'api/v1/admin/orders/'.self::$financingOrder->getRawOriginal('id').'/approve';
    }

    public function test_order_approve_for_only_pending_approval_status(): void
    {
        $response = $this->actingAs(self::$userAdmin)
            ->putJson(self::$apiUrl);

        $response->assertStatus(200)->assertJsonPath('data', []);
    }

    public function test_approve_order_update_order_status_successfully(): void
    {
        $response = $this->actingAs(self::$userAdmin)
            ->putJson(self::$apiUrl);

        $response->assertStatus(200)->assertJsonPath('data', []);

        self::$financingOrder = FinancingOrder::find(self::$financingOrder->id);

        $this->assertTrue(self::$financingOrder->status->is(FinancingOrderStatus::Approved));
    }

    public function test_approve_order_fails_if_order_is_approved(): void
    {
        self::$financingOrder = $this->createOrder(self::$company->id, self::$userLender->id, [
            'status' => FinancingOrderStatus::InProgress,
        ]);

        self::$apiUrl = 'api/v1/admin/orders/'.self::$financingOrder->getRawOriginal('id').'/approve';

        $response = $this->actingAs(self::$userAdmin)
            ->putJson(self::$apiUrl);

        $response->assertStatus(400)->assertJson(
            fn (AssertableJson $json) => $json->where('message', __('error.order_cannot_be_approved_because_it_is_approved'))
                ->where('code', 1018)
        );
    }

    public function test_approve_order_for_auth_super_admin(): void
    {
        $response = $this->actingAs(self::$userAdmin)
            ->putJson(self::$apiUrl);

        $response->assertStatus(200)->assertJsonPath('data', []);
    }

    public function test_approve_order_for_auth_manger_admin_with_permission(): void
    {
        Grantify::syncRoleToModel(self::$userAdmin, Role::Manager);
        Grantify::assignPermissionToModel(self::$userAdmin, perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Approve]));

        $response = $this->actingAs(self::$userAdmin)
            ->putJson(self::$apiUrl);

        $response->assertStatus(200)->assertJsonPath('data', []);
    }

    public function test_approve_order_fails_for_manager_admin_without_permission(): void
    {
        Grantify::syncRoleToModel(self::$userAdmin, Role::Manager);

        $response = $this->actingAs(self::$userAdmin)
            ->putJson(self::$apiUrl);

        $response->assertStatus(403)->assertJson(
            fn (AssertableJson $json) => $json->where('message', 'User does not have the right permissions.')
                ->etc()
        );
    }
}
