<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders\Orders\TraderOrders;

use App\Enums\Area;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\TraderOrder;
use App\Models\TraderOrderSettlement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
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
}
