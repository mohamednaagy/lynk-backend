<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders\Orders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\Subject;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class CompleteOrderTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    private static Company $company;

    private static User $userLender;

    private static User $superAdminUser;

    private static User $managerHasPermissions;

    private static User $managerHasNoPermissionPermissions;

    private static Builder|Model $financingOrder;

    private static Builder|Model $traderOrder;

    private static string $apiUrl;

    /**
     * @return void
     */
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
            perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Edit])
        );

        self::$financingOrder = $this->createOrder(
            self::$company->id,
            self::$userLender->id,
            [
                'status' => FinancingOrderStatus::MurabahaSaleCompleted,
            ]
        );

        self::$traderOrder = self::$financingOrder->traderOrders()->create([
            'provider' => 'fake',
            'reference' => '123456789',
            'status' => TraderOrderStatus::Completed,
        ]);

        self::$apiUrl = 'api/v1/admin/orders/'
            .self::$financingOrder->getRawOriginal('id').
            '/complete';
    }

    /**
     * @return void
     */
    public function test_complete_order_unauth_user_cant_make_order_completed(): void
    {
        $this->postJson(self::$apiUrl)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_complete_order_only_roles_of_super_admin_area_can_access(): void
    {
        $this->assertStatusCodeForAllRolesExceptForArea(403, [Area::SuperAdmin], function ($user, $role) {
            return $this->actingAs($user)
                ->postJson(self::$apiUrl, [
                    'payment_proof' => UploadedFile::fake()->create('payment_proof.pdf'),
                ]);
        });
    }

    public function test_complete_order_super_admin_can_access()
    {
        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$apiUrl, [
                'payment_proof' => UploadedFile::fake()->create('payment_proof.pdf'),
            ])->assertStatus(Response::HTTP_OK);
    }

    public function test_complete_order_that_manager_with_permissions_can_access()
    {
        $this->actingAs(self::$managerHasPermissions)
            ->postJson(self::$apiUrl, [
                'payment_proof' => UploadedFile::fake()->create('payment_proof.pdf'),
            ])->assertStatus(Response::HTTP_OK);
    }

    public function test_complete_order_that_manager_without_permissions_can_not_access()
    {
        $this->actingAs(self::$managerHasNoPermissionPermissions)
            ->postJson(self::$apiUrl, [
                'payment_proof' => UploadedFile::fake()->create('payment_proof.pdf'),
            ])->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @return void
     */
    public function test_complete_order_payment_proof_file_is_not_required(): void
    {
        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$apiUrl)
            ->assertOk();

        self::$financingOrder->fresh()->status->is(FinancingOrderStatus::Completed);
    }

    /**
     * @return void
     */
    public function test_complete_order_payment_proof_file_should_be_supported_type(): void
    {
        $this->actingAs(self::$superAdminUser)
            ->postJson(
                self::$apiUrl,
                [
                    'payment_proof' => UploadedFile::fake()->create('payment_proof.xlx'),
                ]
            )
            ->assertJsonValidationErrorFor('payment_proof');
    }

    /**
     * @return void
     */
    public function test_complete_order_successfully(): void
    {
        self::$traderOrder->traderHistories()->create([
            'action' => FinancingOrderHistory::$orderHistoryLastActionMap[FinancingOrderStatus::MurabahaSaleCompleted],
        ]);

        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$apiUrl, [
                'payment_proof' => UploadedFile::fake()->create('payment_proof.pdf'),
            ])->assertStatus(Response::HTTP_OK);

        $this->assertTrue(self::$financingOrder->fresh()->status->is(FinancingOrderStatus::Completed));
        $this->assertTrue(self::$traderOrder->fresh()->status->is(TraderOrderStatus::Completed));
    }

    /**
     * @return void
     */
    public function test_complete_order_will_return_error_response_if_flow_is_not_correct(): void
    {
        self::$financingOrder->traderOrders()->update([
            'status' => TraderOrderStatus::Expired,
        ]);

        $statuses = FinancingOrderStatus::getValues();
        foreach ($statuses as $status) {
            if ($status == FinancingOrderStatus::Completed) {
                continue;
            }

            self::$financingOrder->update([
                'status' => $status,
            ]);
            self::$financingOrder->refresh();

            $this->actingAs(self::$superAdminUser)
                ->postJson(self::$apiUrl, [
                    'payment_proof' => UploadedFile::fake()->create('payment_proof.pdf'),
                ])
                ->assertStatus(Response::HTTP_BAD_REQUEST)
                ->assertJsonFragment([
                    'message' => __('error.order_status_doesnt_follow_sequence'),
                ]);
        }
    }
}
