<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\FinancingOrders;

use App\Enums\Area;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
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

class UpdateOrderPaymentProofTest extends TestCase
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
                'status' => FinancingOrderStatus::Completed,
            ]
        );

        self::$traderOrder = self::$financingOrder->traderOrders()->create([
            'provider' => 'fake',
            'reference' => '123456789',
            'status' => TraderOrderStatus::InProgress,
        ]);

        self::$apiUrl = 'api/v1/lender/orders/'.self::$financingOrder->getRawOriginal('id').'/payment-proof';
    }

    /**
     * @return void
     */
    public function test_update_order_payment_proof_that_unauth_user_cant_make_order_completed(): void
    {
        $this->withHeader('X-Company', self::$company->getOriginal('id'))
            ->putJson(self::$apiUrl)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_update_order_payment_proof_that_only_roles_in_super_admin_area_users_can_access(): void
    {
        $this->assertStatusCodeForAllRolesExceptForArea(403, [Area::Lender], function ($user, $role) {
            return $this->actingAs($user)
                ->withHeader('X-Company', self::$company->getOriginal('id'))
                ->putJson(self::$apiUrl);
        });
    }

    /**
     * @return void
     */
    public function test_update_order_payment_proof_that_only_lender_admin_and_supervisor_and_creator_can_access(): void
    {
        $rolesHasAccess = [
            Role::LenderAdmin,
            Role::LenderSupervisor,
            Role::LenderOrderCreator,
        ];

        $rolesHasNoAccess = [
            Role::LenderBilling,
            Role::LenderApiUser,
        ];

        foreach ($rolesHasAccess as  $role) {
            $user = $this->createLenderUser(self::$company->id, $role);
            $this->actingAs($user)
                ->withHeader('X-Company', self::$company->id)
                ->putJson(self::$apiUrl, [
                    'payment_proof' => UploadedFile::fake()->create('payment_proof.pdf'),
                ])->assertStatus(Response::HTTP_OK);
        }

        foreach ($rolesHasNoAccess as  $role) {
            $user = $this->createLenderUser(self::$company->id, $role);
            $this->actingAs($user)
                ->withHeader('X-Company', self::$company->id)
                ->putJson(self::$apiUrl, [
                    'payment_proof' => UploadedFile::fake()->create('payment_proof.pdf'),
                ])->assertStatus(Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * @return void
     */
    public function test_update_order_payment_proof_that_payment_proof_file_is_required(): void
    {
        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->putJson(self::$apiUrl)
            ->assertJsonValidationErrorFor('payment_proof');
    }

    /**
     * @return void
     */
    public function test_update_order_payment_proof_that_payment_proof_file_should_be_supported_type(): void
    {
        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
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
    public function test_update_order_payment_proof_that_order_not_follow_the_sequence(): void
    {
        $statuses = FinancingOrderStatus::getValues();
        foreach ($statuses as $status) {
            if ($status == FinancingOrderStatus::Completed) {
                continue;
            }

            self::$financingOrder->update(['status' => $status]);
            self::$financingOrder->refresh();
            $this->actingAs(self::$userLender)
                ->withHeader('X-Company', self::$company->getOriginal('id'))
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

        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->putJson(self::$apiUrl, [
                'payment_proof' => UploadedFile::fake()->create('payment_proof.pdf'),
            ])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonFragment([
                'payment_proof_url' => self::$financingOrder->getFirstMedia(FinancingOrderMediaCollection::PaymentProofFromLenderToCustomer)?->fileUrl,
            ]);
    }
}
