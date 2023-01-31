<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders;

use App\Enums\Area;
use App\Enums\ErrorCode;
use App\Enums\FinancingOrderProceedCase;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Enums\Role;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
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

    private static Builder|Model $financingOrder;

    private static Builder|Model $traderOrder;

    private static string $orderProceedUrl;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany('2000');
        self::$userLender = $this->createLenderUser(self::$company->id);
        self::$admin = $this->createSuperAdminUser(Role::Admin, ['email_verified_at' => now()]);
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
    public function test_admin_proceed_order_on_empty_case(): void
    {
        $this->actingAs(self::$admin)
            ->postJson(self::$orderProceedUrl, [
                'case' => '',
            ])->assertStatus(422)->assertExactJson(
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
        $response = $this->actingAs(self::$admin)
            ->postJson(self::$orderProceedUrl, [
                'case' => 'TEST_PROCEED_CASE',
            ])
            ->assertStatus(422)->assertExactJson(
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
    public function test_admin_proceed_order_contract_signed_case_dosent_proceed_when_order_status_doesnt_follow_sequence(): void
    {
        $statuses = FinancingOrderStatus::getValues();
        foreach ($statuses as $status) {
            self::$financingOrder->status = $status;
            self::$financingOrder->save();
            self::$financingOrder->refresh();

            if (self::$financingOrder->status->cantMoveTo(FinancingOrderStatus::ContractSigned)) {
                $response = $this->actingAs(self::$admin)
                    ->postJson(self::$orderProceedUrl, [
                        'case' => FinancingOrderProceedCase::ContractSigned,
                    ]);

                $response->assertStatus(400)->assertExactJson([
                    'message' => __('error.order_status_doesnt_follow_sequence'),
                    'code' => ErrorCode::ORDER_STATUS_DOESNT_FOLLOW_SEQUENCE,
                ]);
            }
        }
    }

    /**
     * @return void
     */
    public function test_admin_proceed_order_client_wakala_accepted_case_dosent_proceed_when_order_status_doesnt_follow_sequence(): void
    {
        $statuses = FinancingOrderStatus::getValues();
        foreach ($statuses as $status) {
            self::$financingOrder->status = $status;
            self::$financingOrder->save();
            self::$financingOrder->refresh();

            if (self::$financingOrder->status->cantMoveTo(FinancingOrderStatus::ClientWakalaCompleted)) {
                $response = $this->actingAs(self::$admin)
                    ->postJson(self::$orderProceedUrl, [
                        'case' => FinancingOrderProceedCase::ClientWakalaAccepted,
                    ]);

                $response->assertStatus(400)->assertExactJson([
                    'message' => __('error.order_status_doesnt_follow_sequence'),
                    'code' => ErrorCode::ORDER_STATUS_DOESNT_FOLLOW_SEQUENCE,
                ]);
            }
        }
    }

    /**
     * @return void
     */
    public function test_admin_cannot_make_order_proceed_on_client_wakala_accepted_when_order_verification_is_required(): void
    {
        self::$financingOrder->is_verification_required = true;
        self::$financingOrder->status = FinancingOrderStatus::WaitingClientWakala;
        self::$financingOrder->save();

        $this->actingAs(self::$admin)
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ClientWakalaAccepted,
            ])
            ->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * @return void
     */
    public function test_admin_proceed_order_on_contract_signed_successfully(): void
    {
        self::$financingOrder->status = FinancingOrderStatus::CommodityPurchased;
        self::$financingOrder->save();

        $this->actingAs(self::$admin)
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ContractSigned,
            ])
            ->assertStatus(200)->assertJsonStructure([
                'data',
            ]);

        $this->assertEquals(
            FinancingOrder::find(self::$financingOrder->getOriginal('id'))->status->value,
            FinancingOrderStatus::ContractSigned
        );
    }

    /**
     * @return void
     */
    public function test_admin_proceed_order_on_client_wakala_accepted_successfully(): void
    {
        self::$financingOrder->status = FinancingOrderStatus::WaitingClientWakala;
        self::$financingOrder->save();

        $this->actingAs(self::$admin)
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ClientWakalaAccepted,
            ])
            ->assertStatus(200)->assertJsonStructure([
                'data',
            ]);

        $this->assertEquals(
            FinancingOrder::find(self::$financingOrder->getOriginal('id'))->status->value,
            FinancingOrderStatus::ClientWakalaCompleted
        );

        $this->assertTrue(self::$financingOrder->hasMedia(FinancingOrderMediaCollection::ClientWakala));
    }
}
