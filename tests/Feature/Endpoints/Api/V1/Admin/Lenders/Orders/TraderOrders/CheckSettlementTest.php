<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders\Orders\TraderOrders;

use App\Enums\Area;
use App\Enums\TraderOrderSettlementStatus;
use App\Enums\TraderOrderStatus;
use App\Jobs\TraderOrder\CheckTraderOrderSettlementJob;
use App\Models\Company;
use App\Models\TraderOrder;
use App\Models\TraderOrderSettlement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class CheckSettlementTest extends TestCase
{
    use AssertsAccessByRoleAndArea, RefreshDatabase;

    const BaseUrl = 'api/v1/admin';

    private static Company $lender;

    private static User $superAdminUser;

    private static Model $financingOrder;

    private static TraderOrder $traderOrder;

    private static string $url;

    protected function setUp(): void
    {
        parent::setUp();

        self::$superAdminUser = $this->createSuperAdminUser();
        [self::$lender] = $this->createLenderCompany('2000', [
            'company_cr' => '1234567891',
        ]);
        $userLender = $this->createLenderUser(self::$lender->id);
        self::$financingOrder = $this->createOrder(
            self::$lender->id,
            $userLender->id,
            [
                'status' => \App\Enums\FinancingOrderStatus::InProgress,
            ]
        );

        // Create trader order that can be settled (completed + lynk)
        self::$traderOrder = self::$financingOrder->traderOrders()->create([
            'provider' => 'lynk',
            'reference' => 'REF-API',
            'status' => TraderOrderStatus::Completed,
        ]);

        // Pre-create a settlement to test the already-settled path
        TraderOrderSettlement::create([
            'trader_order_id' => self::$traderOrder->id,
            'is_commodities_settled' => true,
            'creator_id' => self::$superAdminUser->id,
        ]);

        self::$url = self::BaseUrl.
            '/orders/'.self::$financingOrder->getOriginal('id').
            '/trader-orders/'.self::$traderOrder->getOriginal('id').
            '/check-settlement';
    }

    public function test_unauthenticated_cannot_check_settlement(): void
    {
        $this->getJson(self::$url)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_only_super_admin_area_can_access(): void
    {
        $this->assertStatusCodeForAllRolesExceptForArea(
            Response::HTTP_FORBIDDEN,
            [Area::SuperAdmin],
            function ($user, $role) {
                return $this->actingAs($user)
                    ->getJson(self::$url);
            }
        );
    }

    public function test_success_returns_settlement_payload(): void
    {
        $this->actingAs(self::$superAdminUser)
            ->getJson(self::$url)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'is_commodities_settled',
                    'message',
                    'creator' => [
                        'id',
                        'name',
                    ],
                ],
            ])
            ->assertJsonPath('data.is_commodities_settled', true);
    }

    public function test_cannot_check_settlement_when_trader_order_is_not_completed(): void
    {
        $traderOrder = self::$financingOrder->traderOrders()->create([
            'provider' => 'lynk',
            'reference' => 'REF-IN-PROGRESS',
            'status' => TraderOrderStatus::InProgress,
        ]);

        $url = self::BaseUrl.
            '/orders/'.self::$financingOrder->getOriginal('id').
            '/trader-orders/'.$traderOrder->getOriginal('id').
            '/check-settlement';

        $this->actingAs(self::$superAdminUser)
            ->getJson($url)
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonStructure([
                'message',
                'code',
            ]);
    }

    public function test_cannot_check_settlement_when_trader_order_is_not_lynk(): void
    {
        $traderOrder = self::$financingOrder->traderOrders()->create([
            'provider' => 'bursam',
            'reference' => 'REF-BURSAM',
            'status' => TraderOrderStatus::Completed,
        ]);

        $url = self::BaseUrl.
            '/orders/'.self::$financingOrder->getOriginal('id').
            '/trader-orders/'.$traderOrder->getOriginal('id').
            '/check-settlement';

        $this->actingAs(self::$superAdminUser)
            ->getJson($url)
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonStructure([
                'message',
                'code',
            ]);
    }

    public function test_creates_new_settlement_check_when_not_already_settled(): void
    {
        // Fake queue to prevent job from actually running
        Queue::fake();

        // Create a new trader order without pre-existing settlement
        $traderOrder = self::$financingOrder->traderOrders()->create([
            'provider' => 'lynk',
            'reference' => 'REF-NEW',
            'status' => TraderOrderStatus::Completed,
        ]);

        $url = self::BaseUrl.
            '/orders/'.self::$financingOrder->getOriginal('id').
            '/trader-orders/'.$traderOrder->getOriginal('id').
            '/check-settlement';

        // Assert no settlement exists before
        $this->assertDatabaseMissing('trader_order_settlements', [
            'trader_order_id' => $traderOrder->id,
        ]);

        $this->actingAs(self::$superAdminUser)
            ->getJson($url)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'is_commodities_settled',
                    'message',
                    'creator',
                ],
            ])->assertJsonPath('data.message', __('error.settlement_check_in_progress'));

        // Assert settlement was created
        $this->assertDatabaseHas('trader_order_settlements', [
            'trader_order_id' => $traderOrder->id,
            'creator_id' => self::$superAdminUser->id,
            'status' => TraderOrderSettlementStatus::Pending,
            'is_commodities_settled' => null,
        ]);

        // Assert job was dispatched but not executed
        Queue::assertPushed(CheckTraderOrderSettlementJob::class);

        // Try to send another check settlement request
        $this->actingAs(self::$superAdminUser)
            ->getJson($url)
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonStructure([
                'message',
            ])->assertJsonPath('message', __('error.pending_settlement_check'));
    }
}
