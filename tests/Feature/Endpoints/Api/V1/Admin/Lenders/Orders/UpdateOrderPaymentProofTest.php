<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders\Orders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
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

class UpdateOrderPaymentProofTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    private static Company $company;

    private static User $userLender;

    private static User $admin;

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
        self::$admin = $this->createSuperAdminUser();
        self::$managerHasPermissions = $this->createSuperAdminUser(Role::Manager);
        self::$managerHasNoPermissionPermissions = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(
            self::$managerHasPermissions,
            perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Edit])
        );

        self::$financingOrder = $this->createOrder(
            self::$company->id,
            self::$admin->id,
            [
                'status' => FinancingOrderStatus::Completed,
            ]
        );

        self::$traderOrder = self::$financingOrder->traderOrders()->create([
            'provider' => 'fake',
            'reference' => '123456789',
            'status' => TraderOrderStatus::InProgress,
        ]);

        self::$apiUrl = 'api/v1/admin/orders/'
            .self::$financingOrder->getRawOriginal('id').
            '/payment-proof';
    }

    /**
     * @return void
     */
    public function test_update_order_payment_proof_that_unauth_user_cant_make_order_completed(): void
    {
        $this->putJson(self::$apiUrl)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_update_order_payment_proof_only_roles_in_super_admin_area_users_can_access(): void
    {
        $this->assertStatusCodeForAllRolesExceptForArea(403, [Area::SuperAdmin], function ($user, $role) {
            return $this->actingAs($user)
                ->putJson(self::$apiUrl);
        });
    }

    public function test_complete_order_that_manager_with_permissions_can_access()
    {
        $this->actingAs(self::$managerHasPermissions)
            ->putJson(self::$apiUrl, [
                'payment_proof' => UploadedFile::fake()->create('payment_proof.pdf'),
            ])
            ->assertStatus(Response::HTTP_OK);
    }

    public function test_complete_order_that_manager_without_permissions_can_not_access()
    {
        $this->actingAs(self::$managerHasNoPermissionPermissions)
            ->putJson(self::$apiUrl, [
                'payment_proof' => UploadedFile::fake()->create('payment_proof.pdf'),
            ])
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @return void
     */
    public function test_update_order_payment_proof_that_payment_proof_file_is_required(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::$apiUrl)
            ->assertJsonValidationErrorFor('payment_proof');
    }

    /**
     * @return void
     */
    public function test_update_order_payment_proof_that_payment_proof_file_should_be_supported_type(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(
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
    public function test_update_order_payment_proof_will_return_error_response_if_flow_is_not_correct(): void
    {
        $statuses = FinancingOrderStatus::getValues();
        foreach ($statuses as $status) {
            if ($status == FinancingOrderStatus::Completed) {
                continue;
            }

            self::$financingOrder->update(['status' => $status]);
            self::$financingOrder->refresh();
            $this->actingAs(self::$admin)
                ->putJson(self::$apiUrl, [
                    'payment_proof' => UploadedFile::fake()->create('payment_proof.pdf'),
                ])
                ->assertStatus(Response::HTTP_BAD_REQUEST)
                ->assertJsonFragment([
                    'message' => __('error.order_status_doesnt_follow_sequence'),
                ]);
        }
    }

    /**
     * @return void
     */
    public function test_update_order_payment_proof_that_successfully(): void
    {
        self::$financingOrder->update(['status' => FinancingOrderStatus::Completed]);
        self::$financingOrder->refresh();

        $this->actingAs(self::$admin)
            ->putJson(self::$apiUrl, [
                'payment_proof' => UploadedFile::fake()->create('payment_proof.pdf'),
            ])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonFragment([
                'payment_proof_url' => self::$financingOrder->getFirstMedia(FinancingOrderMediaCollection::PaymentProofFromLenderToCustomer)?->file_url,
            ]);
    }
}
