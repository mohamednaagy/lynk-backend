<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders\Orders\TraderOrders;

use App\Enums\Area;
use App\Enums\FinancingOrderHistory;
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

class AdminCompleteOrderTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    private static Company $company;

    private static User $userLender;

    private static User $superAdminUser;

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
            'status' => TraderOrderStatus::InProgress,
        ]);

        self::$apiUrl = 'api/v1/admin/orders/'
            .self::$financingOrder->getRawOriginal('id').
            '/trader-orders/'.self::$traderOrder->id.
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
    public function test_complete_order_only_admin_can_access(): void
    {
        $this->assertStatusCodeForAllRolesExceptForArea(403, [Area::SuperAdmin], function ($user, $role) {
            return $this->actingAs($user)
                ->postJson(self::$apiUrl);
        });
    }

    /**
     * @return void
     */
    public function test_complete_order_payment_proof_file_is_required(): void
    {
        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$apiUrl)
            ->assertJsonValidationErrorFor('payment_proof');
    }

    /**
     * @return void
     */
    public function test_complete_order_payment_proof_file_should_be_pdf_type(): void
    {
        $this->actingAs(self::$superAdminUser)
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
    public function test_complete_order_not_follow_the_sequence(): void
    {
        $statuses = array_keys(FinancingOrderHistory::$orderHistoryLastActionMap);
        foreach ($statuses as $status) {
            if ($status != FinancingOrderStatus::MurabahaSaleCompleted) {
                self::$traderOrder->traderHistories()->create([
                    'action' => FinancingOrderHistory::$orderHistoryLastActionMap[$status],
                ]);

                $this->actingAs(self::$superAdminUser)
                    ->postJson(self::$apiUrl, [
                        'payment_proof' => UploadedFile::fake()->create('payment_proof.pdf'),
                    ])->assertStatus(Response::HTTP_BAD_REQUEST)
                    ->assertJsonFragment([
                        'message' => __('error.order_status_doesnt_follow_sequence'),
                    ]);
            }
        }
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
            ])->assertStatus(Response::HTTP_OK)
            ->assertJsonFragment([
                'payment_proof_url' => self::$financingOrder->getFirstMedia(FinancingOrderMediaCollection::PaymentProof)?->fileUrl,
            ]);

        $this->assertTrue(self::$financingOrder->fresh()->status->is(FinancingOrderStatus::Completed));
        $this->assertTrue(self::$traderOrder->fresh()->status->is(TraderOrderStatus::Completed));
    }
}
