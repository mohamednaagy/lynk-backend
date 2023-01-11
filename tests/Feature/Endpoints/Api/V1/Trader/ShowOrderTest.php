<?php

namespace Tests\Feature\Endpoints\Api\V1\Trader;

use App\Actions\Orders\GetOrderAction;
use App\Enums\FinancingOrderHistory;
use App\Enums\Role;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use App\Transformers\FinancingOrderTransformer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class ShowOrderTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    private static Company $company;

    private static User $userLenderAdmin;

    private static Wallet $wallet;

    private static Builder|Model $order;

    private static Builder|Model $traderOrder;

    private static Builder|Model $traderHistory;

    /**
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'lenderAdmin@bim.com');
        self::$order = $this->createOrder(self::$company->id, self::$userLenderAdmin->id);
        self::$traderOrder = self::$order->traderOrders()->create([
            'provider' => 'fake',
            'status' => TraderOrderStatus::InProgress,
            'reference' => 123,
        ]);
        self::$traderHistory = self::$traderOrder->traderHistories()->create([
            'action' => FinancingOrderHistory::GetTtiId,
        ]);
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_can_show_order(): void
    {
        $this->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/trader/'.self::$order->id.'/order')
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal((new GetOrderAction())->handle(self::$order->id), new FinancingOrderTransformer())
                    ->parseIncludes([
                        'id',
                        'amount',
                        'selling_price',
                        'status',
                        'active_trader',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }
}
