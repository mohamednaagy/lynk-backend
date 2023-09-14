<?php

namespace Jobs\Dmcc\V1;

use App\Enums\DmccMurabhaStep;
use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\User;
use App\Support\Traders\Drivers\Dmcc\Jobs\V1\ProcessDmccSellingCommodityToCustomerOrder;
use CodeDredd\Soap\Facades\Soap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\Support\FinancingOrders\TraderOrderScenario;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class ProcessDmccSellingCommodityToCustomerOrderTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany;

    protected static Company $company;

    protected static User $lender;

    protected static FinancingOrder $order;

    public function setUp(): void
    {
        parent::setUp();
        [self::$company] = $this->createLenderCompany();

        self::$lender = $this->createLenderUser(self::$company->id);

        self::$order = OrderScenario::inProgress()
            ->lender(self::$company)
            ->creator(self::$lender)
            ->commit()
            ->model();

        $inventoryDetails = [
            'inventoryDetails' => [
                [
                    'hsCodeDescription' => 'hsCodeDescription',
                    'quantity' => 100,
                    'totalValue' => 100,
                    'currency' => 'SAR',
                    'warehouseOrVaultId' => 'warehouseOrVaultId',
                    'owner' => 'owner',
                ],
            ],
            'errorCode' => '',
        ];

        Http::fake(function () use ($inventoryDetails) {
            return Http::response($inventoryDetails, 200);
        });

        Soap::fake(function () use ($inventoryDetails) {
            return Soap::response($inventoryDetails, 200);
        });
    }

    /**
     * @throws \Throwable
     */
    public function test_job_process_if_murabha_step_is_not_contract_signed_will_not_work()
    {
        $financeHistories = FinancingOrderHistory::getValues();

        $traderOrder = InProgressOrder::of(self::$order)->createTraderOrder('dmcc', 123);

        foreach ($financeHistories as $financeHistory) {
            if (in_array($financeHistory, [
                FinancingOrderHistory::ClientWakalaAccepted,
                FinancingOrderHistory::GetTtiId,
                FinancingOrderHistory::OrderCancelled,
                FinancingOrderHistory::Expired,
            ])) {
                continue;
            }

            $traderOrder = TraderOrderScenario::of($traderOrder)
                ->reset()
                ->moveToHistory($financeHistory)
                ->getTraderOrder();

            dump($financeHistory);
            $processOrder = new ProcessDmccSellingCommodityToCustomerOrder($traderOrder->id);
            $processOrder->handle();

            $this->assertFalse($traderOrder->hasMedia(TraderOrderMediaCollection::SellingCommodityToCustomer));

            $this->assertTrue($traderOrder->doesLastActionMatchWith($financeHistory));
        }
    }

    /**
     * @throws \Throwable
     */
    public function test_job_process_if_the_active_trader_order_has_dmcc_as_provider_will_work()
    {
        Storage::fake();

        /** @var TraderOrder $traderOrder */
        $traderOrder = InProgressOrder::of(self::$order)->createTraderOrder('dmcc', data: [
            'status' => TraderOrderStatus::InProgress,
            'product' => 'Product',
            'quantity' => 2,
            'amount' => 1000,
            'warehouse' => 'Warehouse ID',
            'owner' => 'Owner 1',
            'previous_owner' => 'Owner 0',
            'new_owner' => 'Owner 1',
            'date_time_of_purchasing_commodity' => now()->format('Y-m-d H:i:s'),
            'warehouse_or_vault_emirates' => 'Vaault',
            'warehouse_or_vault_country' => 'Saudi Arabia',
        ]);

        TraderOrderScenario::of($traderOrder)
            ->reset()
            ->moveToStep(DmccMurabhaStep::ClientWakala);

        $processOrder = new ProcessDmccSellingCommodityToCustomerOrder($traderOrder->id);
        $processOrder->handle();

        $this->assertTrue($traderOrder->hasMedia(TraderOrderMediaCollection::SellingCommodityToCustomer));

        $this->assertTrue($traderOrder->checkOrderHistoryAction(FinancingOrderHistory::CreateSellingCommodityToCustomerDocument));
    }

    /**
     * @throws \Throwable
     */
    public function test_job_process_if_the_active_trader_order_has_fake_as_provider_will_work()
    {
        Storage::fake();

        /** @var TraderOrder $traderOrder */
        $traderOrder = InProgressOrder::of(self::$order)->createTraderOrder('fake');

        TraderOrderScenario::of($traderOrder)
            ->reset()
            ->moveToStep(DmccMurabhaStep::ClientWakala);

        $processOrder = new ProcessDmccSellingCommodityToCustomerOrder($traderOrder->id);
        $processOrder->handle();

        $this->assertTrue($traderOrder->hasMedia(TraderOrderMediaCollection::SellingCommodityToCustomer));

        $this->assertTrue($traderOrder->checkOrderHistoryAction(FinancingOrderHistory::CreateSellingCommodityToCustomerDocument));
    }
}
