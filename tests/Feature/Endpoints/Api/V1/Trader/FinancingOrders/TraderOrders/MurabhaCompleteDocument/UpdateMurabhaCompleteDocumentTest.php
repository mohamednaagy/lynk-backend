<?php

namespace Endpoints\Api\V1\Trader\FinancingOrders\TraderOrders\MurabhaCompleteDocument;

use App\Enums\Area;
use App\Enums\ErrorCode;
use App\Enums\FinancingOrderStatus;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\TraderOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class UpdateMurabhaCompleteDocumentTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    const BaseUrl = 'api/v1/trader';

    private static Company $trader;

    private static Company $lender;

    private static User $userLender;

    private static User $traderAdminUser;

    private static Builder|Model $financingOrder;

    private static TraderOrder $traderOrder;

    private static string $updateMurabhaCompleteDocumentUrl;

    private static array $requestData;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        Artisan::call('module:seed');

        [self::$trader] = $this->createTraderCompany('2000', [
            'company_cr' => '1234567891',
            'driver' => 'fake',
        ]);
        [self::$lender] = $this->createLenderCompany('2000', ['company_cr' => '1234567892']);
        self::$traderAdminUser = $this->createTraderUser(self::$trader->id);
        self::$userLender = $this->createLenderUser(self::$lender->id);
        self::$financingOrder = $this->createOrder(
            self::$lender->id,
            self::$userLender->id,
            [
                'is_verification_required' => true,
                'status' => FinancingOrderStatus::MurabhaOfferIssued,
            ]
        );

        // create trader order
        self::$traderOrder = self::$financingOrder->traderOrders()->create([
            'provider' => self::$trader->driver,
            'reference' => '123456789',
            'status' => TraderOrderStatus::InProgress,
        ]);

        self::$updateMurabhaCompleteDocumentUrl = self::BaseUrl.
            '/orders/'.
            self::$financingOrder->id.
            '/trader-orders/'.
            self::$traderOrder->id.
            '/murabha-complete';

        self::$requestData = [
            'document' => UploadedFile::fake()
                ->create('attachment.pdf', 10),
        ];
    }

    /**
     * @return void
     */
    public function test_that_unauth_user_cant_update_murabha_complete_document(): void
    {
        $this->withHeader('X-Company', self::$trader->id)
            ->postJson(self::$updateMurabhaCompleteDocumentUrl, self::$requestData)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_other_area_roles_of_not_trader_area_cant_update_murabha_complete_document(): void
    {
        $this->assertStatusCodeForAllRolesExceptForArea(
            Response::HTTP_FORBIDDEN,
            [
                Area::Trader,
            ],
            function ($user, $role) {
                return $this->withHeader('X-Company', self::$trader->id)
                    ->actingAs($user)
                    ->postJson(self::$updateMurabhaCompleteDocumentUrl, self::$requestData);
            }
        );
    }

    /**
     * @return void
     */
    public function test_proceed_murabha_complete_document_succeed(): void
    {
        $this->withHeader('X-Company', self::$trader->id)
            ->actingAs(self::$traderAdminUser)
            ->postJson(self::$updateMurabhaCompleteDocumentUrl, self::$requestData)
            ->assertJsonStructure(['data']);
    }

    /**
     * @return void
     */
    public function test_update_murabha_complete_document_not_follow_sequence(): void
    {
        self::$financingOrder->update([
            'status' => FinancingOrderStatus::ContractSigned,
        ]);

        $this->withHeader('X-Company', self::$trader->id)
            ->actingAs(self::$traderAdminUser)
            ->postJson(self::$updateMurabhaCompleteDocumentUrl, self::$requestData)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => __('error.order_status_doesnt_follow_sequence'),
                'code' => ErrorCode::ORDER_STATUS_DOESNT_FOLLOW_SEQUENCE,
            ]);
    }

    /**
     * @return void
     */
    public function test_update_murabha_complete_document_succeed(): void
    {
        self::$traderOrder->traderHistories()->create([
            'action' => FinancingOrderStatus::MurabahaSaleCompleted,
        ]);

        $this->withHeader('X-Company', self::$trader->id)
            ->actingAs(self::$traderAdminUser)
            ->postJson(self::$updateMurabhaCompleteDocumentUrl, self::$requestData)
            ->assertJsonStructure(['data']);
    }
}
