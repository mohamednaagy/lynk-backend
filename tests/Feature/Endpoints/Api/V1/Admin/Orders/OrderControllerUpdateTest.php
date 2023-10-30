<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Orders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use App\Transformers\FinancingOrderTransformer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class OrderControllerUpdateTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany;

    private static Company $company;

    private static Wallet $wallet;

    private static User $userLenderAdmin;

    private static User $admin;

    private static User $mangerHasNoPermissions;

    private static User $managerHasPermissions;

    private static Builder|Model $order;

    private static Builder|Model $orderOwnedByOrderCreator;

    private static array $updatedOrderDetails;

    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompany('2000');
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$admin = $this->createSuperAdminUser();
        self::$mangerHasNoPermissions = $this->createSuperAdminUser(Role::Manager);
        self::$managerHasPermissions = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(
            self::$managerHasPermissions,
            perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Edit])
        );
        self::$order = $this->createOrder(self::$company->id, self::$userLenderAdmin->id, ['status' => FinancingOrderStatus::Rejected]);

        self::$updatedOrderDetails = [
            'national_id' => '2553451234',
            'amount' => '300',
            'selling_price' => '320',
            'phone_country_code' => 'SA',
            'phone_number' => '500112233',
        ];
    }

    public function test_that_un_auth_user_cant_update_order(): void
    {
        $this->putJson('api/v1/admin/orders/'.self::$order->id, self::$updatedOrderDetails)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_that_auth_user_without_national_id_cant_update_order(): void
    {
        $this->actingAs(self::$admin)
            ->putJson('api/v1/admin/orders/'.self::$order->id, Arr::except(self::$updatedOrderDetails, ['national_id']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('national_id');
    }

    public function test_that_auth_user_without_amount_cant_update_order(): void
    {
        $this->actingAs(self::$admin)
            ->putJson('api/v1/admin/orders/'.self::$order->id, Arr::except(self::$updatedOrderDetails, ['amount']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('amount')
            ->assertJsonValidationErrors(['selling_price']);
    }

    public function test_that_auth_user_without_selling_price_cant_update_order(): void
    {
        $this->actingAs(self::$admin)
            ->putJson('api/v1/admin/orders/'.self::$order->id, Arr::except(self::$updatedOrderDetails, ['selling_price']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('selling_price');
    }

    public function test_that_auth_user_without_phone_country_code_cant_update_order(): void
    {
        $this->actingAs(self::$admin)
            ->putJson('api/v1/admin/orders/'.self::$order->id, Arr::except(self::$updatedOrderDetails, ['phone_country_code']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('phone_country_code')
            ->assertJsonValidationErrors(['phone_number']);
    }

    public function test_that_auth_user_without_phone_number_cant_update_order(): void
    {
        $this->actingAs(self::$admin)
            ->putJson('api/v1/admin/orders/'.self::$order->id, Arr::except(self::$updatedOrderDetails, ['phone_number']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('phone_number');
    }

    public function test_that_admin_user_can_update_order_with_valid_data(): void
    {
        $this->actingAs(self::$admin)
            ->putJson('api/v1/admin/orders/'.self::$order->id, self::$updatedOrderDetails)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$order->refresh(), new FinancingOrderTransformer())
                    ->parseIncludes([
                        'id',
                        'status',
                        'reference_number',
                        'national_id',
                        'amount',
                        'amount_formatted',
                        'selling_price',
                        'selling_price_formatted',
                        'is_approved',
                        'status_reason',
                        'phone_country_code',
                        'phone_number',
                        'phone_number_formatted',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_that_manager_admin_without_permission_cant_create_order_with_valid_data(): void
    {
        $this->actingAs(self::$mangerHasNoPermissions)
            ->putJson('api/v1/admin/orders/'.self::$order->id, self::$updatedOrderDetails)
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_that_manager_admin_with_permission_cant_create_order_with_valid_data(): void
    {
        $this->actingAs(self::$managerHasPermissions)
            ->putJson('api/v1/admin/orders/'.self::$order->id, self::$updatedOrderDetails)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$order->refresh(), new FinancingOrderTransformer())
                    ->parseIncludes([
                        'id',
                        'status',
                        'reference_number',
                        'national_id',
                        'amount',
                        'amount_formatted',
                        'selling_price',
                        'selling_price_formatted',
                        'is_approved',
                        'status_reason',
                        'phone_country_code',
                        'phone_number',
                        'phone_number_formatted',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_rejected_order_status_will_be_pending_approval_when_required_otherwise_pending_trader_order(): void
    {
        // Require approval case
        self::$company->update(['does_order_require_approval' => true]);
        $this->actingAs(self::$admin)
            ->putJson('api/v1/admin/orders/'.self::$order->id, self::$updatedOrderDetails)
            ->assertStatus(Response::HTTP_OK);
        $this->assertTrue(self::$order->refresh()->status->is(FinancingOrderStatus::PendingApproval));

        // Approval not Require  case
        self::$company->update(['does_order_require_approval' => false]);
        self::$order->update(['status' => FinancingOrderStatus::Rejected]);
        $this->actingAs(self::$admin)
            ->putJson('api/v1/admin/orders/'.self::$order->id, self::$updatedOrderDetails)
            ->assertStatus(Response::HTTP_OK);
        $this->assertTrue(self::$order->refresh()->status->is(FinancingOrderStatus::PendingTraderOrder));
    }
}
