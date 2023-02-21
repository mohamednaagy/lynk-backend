<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders\Orders\TraderOrders;

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

class AdminUpdateOrderPaymentProofTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    private static Company $company;

    private static User $userLender;

    private static User $admin;

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
            '/trader-orders/'.self::$traderOrder->id.
            '/update-payment-proof';
    }

    /**
     * @return void
     */
    public function test_update_order_payment_proof_unauth_user_cant_make_order_completed(): void
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
    public function test_update_order_payment_proof_only_lender_area_users_can_access(): void
    {
        $this->assertStatusCodeForAllRolesExceptForArea(403, [Area::SuperAdmin], function ($user, $role) {
            return $this->actingAs($user)
                ->postJson(self::$apiUrl);
        });
    }

    /**
     * @return void
     */
    public function test_update_order_payment_proof_payment_proof_file_is_required(): void
    {
        $this->actingAs(self::$admin)
            ->postJson(self::$apiUrl)
            ->assertJsonValidationErrorFor('payment_proof');
    }

    /**
     * @return void
     */
    public function test_update_order_payment_proof_payment_proof_file_should_be_pdf_type(): void
    {
        $this->actingAs(self::$admin)
            ->postJson(
                self::$apiUrl,
                [
                    'payment_proof' => UploadedFile::fake()->create('payment_proof.jpg'),
                ]
            )
            ->assertJsonValidationErrorFor('payment_proof');
    }

    /**
     * @return void
     */
    public function test_update_order_payment_proof_order_not_follow_the_sequence(): void
    {
        $statuses = FinancingOrderStatus::getValues();
        foreach ($statuses as $status) {
            if ($status != FinancingOrderStatus::Completed) {
                self::$financingOrder->update(['status' => $status]);
                self::$financingOrder->refresh();
                $this->actingAs(self::$admin)

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

    /**
     * @return void
     */
    public function test_update_order_payment_proof_successfully(): void
    {
        self::$financingOrder->update(['status' => FinancingOrderStatus::Completed]);
        self::$financingOrder->refresh();

        $this->actingAs(self::$admin)
            ->postJson(self::$apiUrl, [
                'payment_proof' => UploadedFile::fake()->create('payment_proof.pdf'),
            ])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonFragment([
                'payment_proof_url' => self::$financingOrder->getFirstMedia(FinancingOrderMediaCollection::PaymentProof)?->fileUrl,
            ]);
    }
}
