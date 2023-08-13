<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders\Orders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\Subject;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class CancelOrderControllerTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany;

    private static string  $endpoint = 'api/v1/admin/orders/';

    private static Company $company;

    private static User $userLender;

    private static Builder|Model $financingOrder;

    private static string $orderCancledUrl;

    private static User $superAdminUser;

    private static User $managerHasPermissions;

    private static User $managerHasNoPermissionPermissions;

    public function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany('2000', ['company_cr' => '1234567891']);
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$superAdminUser = $this->createSuperAdminUser();
        self::$managerHasPermissions = $this->createSuperAdminUser(Role::Manager);
        self::$managerHasNoPermissionPermissions = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(
            self::$managerHasPermissions,
            perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Cancel])
        );
        self::$financingOrder = $this->createOrder(
            self::$company->id,
            self::$userLender->id,
            [
                'is_verification_required' => true,
                'status' => FinancingOrderStatus::InProgress,
            ]
        );
        self::$orderCancledUrl = self::$endpoint.self::$financingOrder->getRawOriginal('id').'/cancel';

        self::$financingOrder->traderOrders()->create([
            'provider' => 'fake',
            'reference' => '123456789',
            'status' => TraderOrderStatus::InProgress,
        ]);
    }

    public function test_unauth_user_cannot_cancel_order(): void
    {
        $this->putJson(self::$orderCancledUrl)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_admin_can_cancel_order_successfully(): void
    {
        $this->actingAs(self::$superAdminUser)
            ->putJson(self::$orderCancledUrl, [
                'status_reason' => 'test reason',
            ])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data', []);
    }

    public function test_admin_manager_with_permissions_can_cancel_order_successfully(): void
    {
        Grantify::syncRoleToModel(self::$managerHasPermissions, Role::Manager);
        $this->actingAs(self::$managerHasPermissions)
            ->putJson(self::$orderCancledUrl, [
                'status_reason' => 'test reason',
            ])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data', []);
    }

    public function test_admin_manager_without_permissions_cant_cancel_order(): void
    {
        $this->actingAs(self::$managerHasNoPermissionPermissions)
            ->putJson(self::$orderCancledUrl, [
                'status_reason' => 'test reason',
            ])
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', 'User does not have the right permissions.');
    }

    public function test_admin_can_cancel_order_with_empty_status_reason_successfully(): void
    {
        $this->actingAs(self::$superAdminUser)
            ->putJson(self::$orderCancledUrl, [
                'status_reason' => '',
            ])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data', []);
    }

    /**
     * @dataProvider notCancellableStatusesDataProvider
     *
     * @return void
     */
    public function test_admin_cant_cancel_order_with_not_cancellable_statuses($status)
    {
        self::$financingOrder->update(['status' => $status]);

        $this->actingAs(self::$superAdminUser)
            ->putJson(self::$orderCancledUrl)->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonFragment([
                'message' => __('error.unable_to_cancel_order'),
                'code' => 1010,
            ]);
    }

    /**
     * @dataProvider cancellableStatusesDataProvider
     *
     * @return void
     */
    public function test_admin_can_cancel_order_with_cancellable_statuses_successfully($status)
    {
        self::$financingOrder->update(['status' => $status]);
        $this->actingAs(self::$superAdminUser)
            ->putJson(self::$orderCancledUrl)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data', []);
    }

    public function cancellableStatusesDataProvider()
    {
        return [
            [FinancingOrderStatus::Rejected],
            [FinancingOrderStatus::Approved],
            [FinancingOrderStatus::PendingApproval],
            [FinancingOrderStatus::PendingTraderOrder],
        ];
    }

    public function notCancellableStatusesDataProvider()
    {
        return [
            [FinancingOrderStatus::Cancelled],
            [FinancingOrderStatus::Completed],
            [FinancingOrderStatus::PendingCancellation],
        ];
    }
}
