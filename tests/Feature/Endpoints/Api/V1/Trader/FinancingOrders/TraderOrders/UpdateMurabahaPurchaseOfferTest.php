<?php

namespace Endpoints\Api\V1\Trader\FinancingOrders\TraderOrders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\Subject;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class UpdateMurabahaPurchaseOfferTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    private static Company $company;

    private static User $traderAdminUser;

    private static Builder|Model $order;

    private static Builder|Model $traderOrder;

    private static string $apiUrl;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createTraderCompany('2000', ['company_cr' => '12345678911']);
        self::$traderAdminUser = $this->createTraderUser(self::$company->id);
        self::$order = $this->createOrder(self::$company->id, self::$traderAdminUser->id, [
            'status' => FinancingOrderStatus::ClientWakalaCompleted,
        ]);

        self::$traderOrder = self::$order->traderOrders()->create([
            'provider' => 'fake',
            'status' => TraderOrderStatus::InProgress,
            'reference' => 123,
            'client_wakala_accepted_at' => now(),
        ]);

        self::$traderOrder->traderHistories()->create([
            'action' => FinancingOrderHistory::ClientWakalaAccepted,
        ]);

        self::$apiUrl = 'api/v1/trader/orders/'.self::$order->id.'/trader-orders/'.self::$traderOrder->id.'/murabaha-purchase-offer';
    }

    /**
     * @return void
     */
    public function test_unauth_user_cannot_access(): void
    {
        $this->withHeader('X-Company', self::$company->id)
            ->postJson(self::$apiUrl)
            ->assertUnauthorized();
    }

    public function test_auth_user_can_update_process_murabaha_purchase_offer(): void
    {
        $this->actingAs(self::$traderAdminUser)
            ->withHeader('X-Company', self::$company->id)
            ->postJson(self::$apiUrl, [
                'document' => UploadedFile::fake()->create('test.pdf'),
            ])
            ->assertOk()
            ->assertJsonStructure([
                'data' => [],
            ]);

        self::$order->update(['status' => FinancingOrderStatus::MurabahaSaleCompleted]);

        $this->actingAs(self::$traderAdminUser)
            ->withHeader('X-Company', self::$company->id)
            ->postJson(self::$apiUrl, [
                'document' => UploadedFile::fake()->create('test.pdf'),
            ])
            ->assertOk()
            ->assertJsonStructure([
                'data' => [],
            ]);

        $this->assertTrue(self::$order->fresh()->status->is(FinancingOrderStatus::MurabahaSaleCompleted));
    }

    public function test_auth_user_can_update_process_murabaha_purchase_offer_with_trader_not_in_progress(): void
    {
        self::$traderOrder->update(['status' => TraderOrderStatus::Completed]);
        self::$order->update(['status' => FinancingOrderStatus::MurabahaSaleCompleted]);

        $this->actingAs(self::$traderAdminUser)
            ->withHeader('X-Company', self::$company->id)
            ->postJson(self::$apiUrl, [
                'document' => UploadedFile::fake()->create('test.pdf'),
            ])
            ->assertOk()
            ->assertJsonStructure([
                'data' => [],
            ]);

        $this->assertTrue(self::$order->fresh()->status->is(FinancingOrderStatus::MurabahaSaleCompleted));
    }

    public function test_other_users_areas_can_not_update_process_murabaha_purchase_offer_with_invalid_permissions()
    {
        $this->assertStatusCodeExceptForPermissions(Response::HTTP_FORBIDDEN,
            [
                Area::Trader => [
                    [Subject::All, Action::Manage],
                    [Subject::FinancingOrders, Action::Edit],
                    [Subject::FinancingOrders, Action::Manage],
                ],
            ], function ($user, $role, $permission) {
                return $this->actingAs($user)
                    ->withHeader('X-Company', self::$company->id)
                    ->postJson(self::$apiUrl);
            });
    }
}
