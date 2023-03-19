<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\ErrorCode;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderProceedCase;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\Role;
use App\Enums\Subject;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\User;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\Support\FinancingOrders\TraderOrderScenario;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class MakeOrderProceedTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    private static Company $company;

    private static User $userLender;

    private static User $admin;

    private static User $adminManagerWithoutPermissions;

    private static User $adminManagerWithPermissions;

    private static Builder|Model|FinancingOrder $financingOrder;

    private static Builder|Model|TraderOrder $traderOrder;

    private static \Closure $orderProceedUrl;

    /**
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany('2000');
        self::$userLender = $this->createLenderUser(self::$company->id);

        self::$admin = $this->createSuperAdminUser(Role::Admin, ['email_verified_at' => now()]);

        self::$adminManagerWithoutPermissions = $this->createSuperAdminUser(Role::Manager, ['email_verified_at' => now()]);

        self::$adminManagerWithPermissions = $this->createSuperAdminUser(Role::Manager, ['email_verified_at' => now()]);
        $this->assignPermissionToUser(
            self::$adminManagerWithPermissions,
            perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Edit])
        );

        $inProgressOrder = OrderScenario::inProgress();
        $traderOrder = $inProgressOrder->createTraderOrderWithLastHistory(
            config('trader.default'),
            '123456',
            TraderOrderStatus::In
        )

        self::$financingOrder = $this->createOrder(
            self::$company->id,
            self::$userLender->id,
            [
                'is_verification_required' => false,
                'status' => FinancingOrderStatus::PendingApproval,
            ]
        );

        self::$traderOrder = self::$financingOrder->traderOrders()->create([
            'provider' => 'dmcc',
            'reference' => '123456789',
            'status' => TraderOrderStatus::InProgress,
        ]);

        self::$orderProceedUrl = fn (FinancingOrder $order, TraderOrder $traderOrder) => 'api/v1/admin/'
            . 'orders/' . $order->id
            . '/trader-orders/' . $traderOrder->id . '/proceed';
    }

    /**
     * @return void
     */
    public function test_that_unauth_user_cant_admin_proceed_order(): void
    {
        $this->postJson(self::$orderProceedUrl)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_admin_proceed_order_only_super_admin_can_access(): void
    {
        $this->assertStatusCodeForAllRolesExceptForArea(401, [Area::SuperAdmin], function () {
            return $this->postJson(self::$orderProceedUrl);
        });
    }

    /**
     * @return void
     */
    public function test_admin_proceed_order_manager_can_access_without_permissions(): void
    {
        $this->actingAs(self::$adminManagerWithoutPermissions)
            ->postJson(self::$orderProceedUrl)
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_admin_proceed_order_on_empty_case(): void
    {
        $this->actingAs(self::$admin)
            ->postJson(self::$orderProceedUrl, [
                'case' => '',
            ])
            ->assertStatus(422)
            ->assertExactJson(
                [
                    'message' => 'The case field is required.',
                    'errors' => [
                        'case' => [
                            'The case field is required.',
                        ],
                    ],
                ]
            );
    }

    /**
     * @return void
     */
    public function test_admin_proceed_order_client_wakala_file_required_when_case_is_client_wakala_accepted_and_order_verification_is_false(): void
    {
        self::$financingOrder->update([
            'is_verification_required' => false,
        ]);

        $this->actingAs(self::$admin)
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ClientWakalaAccepted,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('client_wakala');
    }

    /**
     * @return void
     */
    public function test_admin_proceed_order_client_wakala_should_be_pdf_or_jpg_png_file(): void
    {
        self::$financingOrder->update([
            'is_verification_required' => true,
        ]);

        $this->actingAs(self::$admin)
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ClientWakalaAccepted,
                'client_wakala' => UploadedFile::fake()->create('client_wakala.gif'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('client_wakala');
    }

    /**
     * @return void
     */
    public function test_admin_proceed_order_on_invalid_case(): void
    {
        $this->actingAs(self::$admin)
            ->postJson(self::$orderProceedUrl, [
                'case' => 'TEST_PROCEED_CASE',
            ])
            ->assertStatus(422)
            ->assertExactJson(
                [
                    'message' => 'The value you have entered is invalid.',
                    'errors' => [
                        'case' => [
                            'The value you have entered is invalid.',
                        ],
                    ],
                ]
            );
    }

    /**
     * @return void
     */
    public function test_admin_proceed_order_manager_can_access_with_permissions(): void
    {
        self::$financingOrder->update([
            'status' => FinancingOrderStatus::CommodityPurchased,
        ]);

        self::$traderOrder->traderHistories()->create([
            'action' => FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
        ]);

        $this->actingAs(self::$adminManagerWithPermissions)
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ContractSigned,
            ])
            ->assertStatus(200);
    }

    /**
     * @return void
     */
    public function test_admin_proceed_order_on_contract_signed_successfully(): void
    {
        self::$financingOrder->update([
            'status' => FinancingOrderStatus::CommodityPurchased,
        ]);

        self::$traderOrder->traderHistories()->create([
            'action' => FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
        ]);

        $this->actingAs(self::$admin)
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ContractSigned,
            ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);

        $this->assertEquals(
            FinancingOrderStatus::ContractSigned,
            FinancingOrder::find(self::$financingOrder->id)->status->value
        );
    }

    /**
     * @return void
     */
    public function test_admin_reprocessed_order_on_contract_signed_successfully(): void
    {
        self::$financingOrder->update([
            'status' => FinancingOrderStatus::MurabhaOfferIssued,
        ]);

        self::$traderOrder->traderHistories()->create([
            'action' => FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
        ]);

        self::$traderOrder->traderHistories()->create([
            'action' => FinancingOrderHistory::ContractSigned,
        ]);

        $this->actingAs(self::$admin)
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ContractSigned,
            ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);

        $this->assertEquals(
            FinancingOrderStatus::MurabhaOfferIssued,
            FinancingOrder::find(self::$financingOrder->id)->status->value
        );
    }

    /**
     * @return void
     */
    public function test_admin_proceed_order_on_client_wakala_accepted_successfully_when_verification_is_not_required(): void
    {
        self::$financingOrder->update([
            'status' => FinancingOrderStatus::WaitingClientWakala,
            'is_verification_required' => false,
        ]);

        self::$traderOrder->traderHistories()->create([
            'action' => FinancingOrderHistory::ContractSigned,
        ]);

        $this->actingAs(self::$admin)
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ClientWakalaAccepted,
                'client_wakala' => UploadedFile::fake()->create('client_wakala.pdf'),
            ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);

        $this->assertEquals(
            FinancingOrder::find(self::$financingOrder->id)->status->value,
            FinancingOrderStatus::ClientWakalaCompleted
        );

        $this->assertTrue(self::$traderOrder->hasMedia(TraderOrderMediaCollection::SignedClientWakala));
    }

    /**
     * @return void
     */
    public function test_admin_processed_order_on_client_wakala_accepted_successfully(): void
    {
        self::$financingOrder->update([
            'is_verification_required' => true,
            'status' => FinancingOrderStatus::WaitingClientWakala,
        ]);

        self::$traderOrder->traderHistories()->create([
            'action' => FinancingOrderHistory::ContractSigned,
        ]);

        $this->actingAs(self::$admin)
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ClientWakalaAccepted,
            ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);

        $this->assertEquals(
            FinancingOrderStatus::ClientWakalaCompleted,
            FinancingOrder::find(self::$financingOrder->id)->status->value
        );

        $this->assertFalse(self::$traderOrder->hasMedia(TraderOrderMediaCollection::SignedClientWakala));
    }

    /**
     * @return void
     */
    public function test_admin_reproceed_order_on_client_wakala_accepted_successfully_when_verification_is_required(): void
    {
        self::$financingOrder->update([
            'is_verification_required' => true,
            'status' => FinancingOrderStatus::MurabhaOfferIssued,
        ]);

        self::$traderOrder->traderHistories()->create([
            'action' => FinancingOrderHistory::ContractSigned,
        ]);

        self::$traderOrder->traderHistories()->create([
            'action' => FinancingOrderHistory::ClientWakalaAccepted,
        ]);

        $this->actingAs(self::$admin)
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ClientWakalaAccepted,
            ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);

        $this->assertEquals(
            FinancingOrderStatus::MurabhaOfferIssued,
            FinancingOrder::find(self::$financingOrder->id)->status->value
        );

        $this->assertFalse(self::$traderOrder->hasMedia(TraderOrderMediaCollection::SignedClientWakala));
    }

    /**
     * @return void
     */
    public function test_make_order_proceed_on_order_status_doesnt_follow_sequence_for_contract_signed(): void
    {
        self::$traderOrder->traderHistories()->delete();

        $response = $this->actingAs(self::$admin)
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ContractSigned,
            ]);

        $response->assertStatus(400)
            ->assertExactJson([
                'message' => __('error.order_status_doesnt_follow_sequence'),
                'code' => ErrorCode::ORDER_STATUS_DOESNT_FOLLOW_SEQUENCE,
            ]);
    }

    /**
     * @return void
     */
    public function test_make_order_proceed_on_order_status_doesnt_follow_sequence_for_client_wakala_accepted(): void
    {
        self::$traderOrder->traderHistories()->delete();

        $response = $this->actingAs(self::$admin)
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ClientWakalaAccepted,
                'client_wakala' => UploadedFile::fake()->create('client_wakala.pdf'),
            ]);

        $response->assertStatus(400)
            ->assertExactJson([
                'message' => __('error.order_status_doesnt_follow_sequence'),
                'code' => ErrorCode::ORDER_STATUS_DOESNT_FOLLOW_SEQUENCE,
            ]);
    }
}
