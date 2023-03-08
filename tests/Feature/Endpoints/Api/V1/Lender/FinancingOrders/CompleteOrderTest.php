<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\FinancingOrders;

use App\Enums\Area;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
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

        self::$apiUrl = 'api/v1/lender/orders/'.self::$financingOrder->getRawOriginal('id').'/complete';
    }

    /**
     * @return void
     */
    public function test_complete_order_unauth_user_cant_make_order_completed(): void
    {
        $this->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson(self::$apiUrl)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_complete_order_only_lender_area_users_can_access(): void
    {
        $this->assertStatusCodeForAllRolesExceptForArea(403, [Area::Lender], function ($user, $role) {
            return $this->actingAs($user)
                ->withHeader('X-Company', self::$company->getOriginal('id'))
                ->postJson(self::$apiUrl);
        });
    }

    /**
     * @return void
     */
    public function test_complete_order_that_only_lender_billing_lender_api_user_can_not_access(): void
    {
        $rolesHasNoAccess = [
            Role::LenderBilling,
            Role::LenderApiUser,
        ];

        $this->assertStatusCodeToSpecificRoles(Response::HTTP_FORBIDDEN, $rolesHasNoAccess, function ($user, $role) {
            self::$financingOrder->update(['status' => FinancingOrderStatus::MurabahaSaleCompleted]);
            self::$financingOrder->refresh();

            return $this->actingAs($user)
                ->withHeader('X-Company', self::$company->id)
                ->postJson(self::$apiUrl, [
                    'payment_proof' => UploadedFile::fake()->create('payment_proof.pdf'),
                ]);
        });
    }

    public function test_complete_order_that_lender_admin_can_access()
    {
        $user = $this->createLenderUser(self::$company->id, Role::LenderAdmin);

        $this->actingAs($user)
            ->withHeader('X-Company', self::$company->id)
            ->postJson(self::$apiUrl, [
                'payment_proof' => UploadedFile::fake()->create('payment_proof.pdf'),
            ])
            ->assertStatus(Response::HTTP_OK);
    }

    public function test_complete_order_that_lender_supervisor_can_access()
    {
        $user = $this->createLenderUser(self::$company->id, Role::LenderSupervisor);

        $this->actingAs($user)
            ->withHeader('X-Company', self::$company->id)
            ->postJson(self::$apiUrl, [
                'payment_proof' => UploadedFile::fake()->create('payment_proof.pdf'),
            ])
            ->assertStatus(Response::HTTP_OK);
    }

    public function test_complete_order_that_lender_order_creator_can_access()
    {
        $user = $this->createLenderUser(self::$company->id, Role::LenderOrderCreator);

        $this->actingAs($user)
            ->withHeader('X-Company', self::$company->id)
            ->postJson(self::$apiUrl, [
                'payment_proof' => UploadedFile::fake()->create('payment_proof.pdf'),
            ])
            ->assertStatus(Response::HTTP_OK);
    }

    /**
     * @return void
     */
    public function test_complete_order_payment_proof_file_is_not_required(): void
    {
        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson(self::$apiUrl)
            ->assertOk();

        self::$financingOrder->fresh()->status->is(FinancingOrderStatus::Completed);
    }

    /**
     * @return void
     */
    public function test_complete_order_payment_proof_file_should_be_supported_type(): void
    {
        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
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

        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson(self::$apiUrl, [
                'payment_proof' => UploadedFile::fake()->create('payment_proof.pdf'),
            ])
            ->assertStatus(Response::HTTP_OK);
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

            $this->actingAs(self::$userLender)
                ->withHeader('X-Company', self::$company->getOriginal('id'))
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
