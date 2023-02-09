<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders;

use App\Enums\Action;
use App\Enums\Area;
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

    private static string $orderProceedUrl;

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

        self::$orderProceedUrl = 'api/v1/admin/lenders/'
            .self::$company->id.
            '/orders/'.self::$financingOrder->id
            .'/trader-orders/'.self::$traderOrder->id.'/proceed';
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
            ->assertStatus(Response::HTTP_FORBIDDEN);
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
    public function test_admin_proceed_order_contract_signed_case_proceed_when_order_status__with_trader_order_not_in_progress(): void
    {
        $statuses = FinancingOrderStatus::getValues();

        self::$traderOrder->update(['status' => TraderOrderStatus::Cancelled]);

        foreach ($statuses as $status) {
            FinancingOrder::withoutEvents(function () use ($status) {
                self::$financingOrder->update([
                    'status' => $status,
                ]);
            });

            if (self::$financingOrder->status->cantMoveTo(FinancingOrderStatus::ContractSigned)) {
                $this->actingAs(self::$admin)
                    ->postJson(self::$orderProceedUrl, [
                        'case' => FinancingOrderProceedCase::ContractSigned,
                    ])->assertOk();

                self::$financingOrder = self::$financingOrder->fresh();
                $this->assertTrue(self::$financingOrder->status->is($status));
            }
        }
    }

    /**
     * @return void
     */
    public function test_admin_proceed_order_client_wakala_accepted_case_proceed_when_order_status_with_trader_order_client_wakala_accepted(): void
    {
        $statuses = FinancingOrderStatus::getValues();

        self::$traderOrder->update(['client_wakala_accepted_at' => now()]);

        foreach ($statuses as $status) {
            self::$financingOrder->update([
                'status' => $status,
            ]);

            if (self::$financingOrder->status->cantMoveTo(FinancingOrderStatus::ClientWakalaCompleted)) {
                $this->actingAs(self::$admin)
                    ->postJson(self::$orderProceedUrl, [
                        'case' => FinancingOrderProceedCase::ClientWakalaAccepted,
                    ])->assertOk();

                self::$financingOrder = self::$financingOrder->fresh();
                $this->assertTrue(self::$financingOrder->status->is($status));
            }
        }
    }

    /**
     * @return void
     */
    public function test_admin_proceed_order_client_wakala_accepted_case_proceed_when_order_status_with_trader_order_not_in_progress(): void
    {
        $statuses = FinancingOrderStatus::getValues();

        self::$traderOrder->update(['status' => TraderOrderStatus::Cancelled]);

        foreach ($statuses as $status) {
            FinancingOrder::withoutEvents(function () use ($status) {
                self::$financingOrder->update([
                    'status' => $status,
                ]);
            });

            if (self::$financingOrder->status->cantMoveTo(FinancingOrderStatus::ClientWakalaCompleted)) {
                $this->actingAs(self::$admin)
                    ->postJson(self::$orderProceedUrl, [
                        'case' => FinancingOrderProceedCase::ClientWakalaAccepted,
                    ])->assertOk();

                self::$financingOrder = self::$financingOrder->fresh();
                $this->assertTrue(self::$financingOrder->status->is($status));
            }
        }
    }

    /**
     * @return void
     */
    public function test_admin_proceed_order_manager_can_access_with_permissions(): void
    {
        self::$financingOrder->update([
            'status' => FinancingOrderStatus::CommodityPurchased,
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

        $this->actingAs(self::$admin)
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ContractSigned,
            ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);

        $this->assertEquals(
            FinancingOrder::find(self::$financingOrder->id)->status->value,
            FinancingOrderStatus::ContractSigned
        );
    }

    /**
     * @return void
     */
    public function test_admin_proceed_order_on_client_wakala_accepted_successfully(): void
    {
        self::$financingOrder->update([
            'status' => FinancingOrderStatus::WaitingClientWakala,
        ]);

        $this->actingAs(self::$admin)
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ClientWakalaAccepted,
            ])
            ->assertStatus(200)->assertJsonStructure([
                'data',
            ]);

        $this->assertEquals(
            FinancingOrder::find(self::$financingOrder->id)->status->value,
            FinancingOrderStatus::ClientWakalaCompleted
        );

        $this->assertTrue(self::$traderOrder->hasMedia(TraderOrderMediaCollection::ClientWakala));
    }
}
