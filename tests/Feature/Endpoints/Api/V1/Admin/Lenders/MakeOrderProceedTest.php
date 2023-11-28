<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\ErrorCode;
use App\Enums\FinancingOrderProceedCase;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\MurabhaStep;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\TraderOrder;
use App\Models\User;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Tests\Support\FinancingOrders\CommittedOrder;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\Support\FinancingOrders\TraderOrderScenario;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class MakeOrderProceedTest extends TestCase
{
    use AssertsAccessByRoleAndArea, RefreshDatabase;

    private static Company $company;

    private static User $userLender;

    private static User $admin;

    private static User $adminManagerWithoutPermissions;

    private static User $adminManagerWithPermissions;

    private static CommittedOrder $financingOrder;

    private static Builder|Model|TraderOrder $traderOrder;

    private static string $orderProceedUrl;

    /**
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

        self::$financingOrder = OrderScenario::inProgress()
            ->lender(self::$company)
            ->creator(self::$userLender)
            ->commit();

        self::$traderOrder = InProgressOrder::of(self::$financingOrder)->createTraderOrder();

        self::$orderProceedUrl = 'api/v1/admin/'
            .'orders/'.self::$financingOrder->id
            .'/trader-orders/'.self::$traderOrder->id.'/proceed';
    }

    public function test_that_unauth_user_cant_admin_proceed_order(): void
    {
        $this->postJson(self::$orderProceedUrl)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_admin_proceed_order_only_super_admin_can_access(): void
    {
        $this->assertStatusCodeForAllRolesExceptForArea(401, [Area::SuperAdmin], function () {
            return $this->postJson(self::$orderProceedUrl);
        });
    }

    public function test_admin_proceed_order_manager_can_access_without_permissions(): void
    {
        $this->actingAs(self::$adminManagerWithoutPermissions)
            ->postJson(self::$orderProceedUrl)
            ->assertForbidden();
    }

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

    public function test_admin_proceed_order_client_wakala_should_be_pdf_or_jpg_png_file(): void
    {
        self::$financingOrder->requireVerification(false)->commit();

        $this->actingAs(self::$admin)
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ClientWakalaAccepted,
                'client_wakala' => UploadedFile::fake()->create('client_wakala.gif'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('client_wakala');
    }

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

    public function test_admin_proceed_order_manager_can_access_with_permissions(): void
    {
        Event::fake();
        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToStep(
                (new StepHistoriesDictionary(self::$traderOrder->provider, self::$traderOrder->version))
                    ->getPreviousStepOf(MurabhaStep::ContractSigned)->step
            );

        $this->actingAs(self::$adminManagerWithPermissions)
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ContractSigned,
            ])
            ->assertStatus(200);
    }

    public function test_admin_proceed_order_on_contract_signed_successfully(): void
    {
        Event::fake();
        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToStep(
                (new StepHistoriesDictionary(self::$traderOrder->provider, self::$traderOrder->version))
                    ->getPreviousStepOf(MurabhaStep::ContractSigned)->step
            );

        $this->actingAs(self::$admin)
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ContractSigned,
            ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);

        $this->assertTrue(self::$traderOrder->hasMedia(TraderOrderMediaCollection::ClientWakala));
    }

    public function test_admin_reprocessed_order_on_contract_signed_successfully(): void
    {
        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToStep(MurabhaStep::ContractSigned);

        $this->actingAs(self::$admin)
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ContractSigned,
            ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);
    }

    public function test_admin_proceed_order_on_client_wakala_accepted_successfully_when_verification_is_not_required(): void
    {
        self::$financingOrder->requireVerification(false)->commit();

        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToStep(
                (new StepHistoriesDictionary(self::$traderOrder->provider, self::$traderOrder->version))
                    ->getPreviousStepOf(MurabhaStep::ContractSigned)->step
            );

        $this->actingAs(self::$admin)
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ClientWakalaAccepted,
                'client_wakala' => UploadedFile::fake()->create('client_wakala.pdf'),
            ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);

        $this->assertTrue(self::$traderOrder->hasMedia(TraderOrderMediaCollection::SignedClientWakala));
    }

    public function test_admin_processed_order_on_client_wakala_accepted_successfully(): void
    {
        self::$financingOrder->requireVerification(true)->commit();

        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToStep(
                (new StepHistoriesDictionary(self::$traderOrder->provider, self::$traderOrder->version))
                    ->getPreviousStepOf(MurabhaStep::ContractSigned)->step
            );

        $this->actingAs(self::$admin)
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ClientWakalaAccepted,
            ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);

        $this->assertFalse(self::$traderOrder->hasMedia(TraderOrderMediaCollection::SignedClientWakala));
    }

    public function test_admin_reproceed_order_on_client_wakala_accepted_successfully_when_verification_is_required(): void
    {
        self::$financingOrder->requireVerification(true)->commit();

        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToStep(
                (new StepHistoriesDictionary(self::$traderOrder->provider, self::$traderOrder->version))
                    ->getPreviousStepOf(MurabhaStep::ContractSigned)->step
            );

        $this->actingAs(self::$admin)
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ClientWakalaAccepted,
            ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);

        $this->assertFalse(self::$traderOrder->hasMedia(TraderOrderMediaCollection::SignedClientWakala));
    }

    public function test_make_order_proceed_on_order_status_doesnt_follow_sequence_for_contract_signed(): void
    {
        TraderOrderScenario::of(self::$traderOrder)->reset();

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

    public function test_make_order_proceed_on_order_status_doesnt_follow_sequence_for_client_wakala_accepted(): void
    {
        TraderOrderScenario::of(self::$traderOrder)->reset();

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
