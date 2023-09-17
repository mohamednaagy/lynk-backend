<?php

namespace Jobs\Dmcc\V1;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\User;
use App\Support\Traders\Drivers\Dmcc\Jobs\V1\ProcessDmccSellingCommodityToCustomerOrder;
use CodeDredd\Soap\Facades\Soap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
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
        Event::fake();
        $financeHistories = FinancingOrderHistory::getValues();

        $traderOrder = InProgressOrder::of(self::$order)->createTraderOrder('dmcc', 123);

        foreach ($financeHistories as $financeHistory) {
            if (in_array($financeHistory, [
                FinancingOrderHistory::ContractSigned,
                FinancingOrderHistory::ClientWakalaAccepted,
                FinancingOrderHistory::GetTtiId,
                FinancingOrderHistory::OrderCancelled,
                FinancingOrderHistory::Expired,
                FinancingOrderHistory::CommoditySoldToMarket, // not for DMCC
                FinancingOrderHistory::GetOwnershipToCustomerCertificate, // not for DMCC
                FinancingOrderHistory::GetSellingToMarketCertificate, // not for DMCC
            ])) {
                continue;
            }

            try {
                $traderOrder = TraderOrderScenario::of($traderOrder)
                    ->reset()
                    ->moveToHistory($financeHistory)
                    ->getTraderOrder();
            } catch (\Throwable $exception) {
                dump($financeHistory);
            }
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
        Event::fake();
        /** @var TraderOrder $traderOrder */
        $traderOrder = InProgressOrder::of(self::$order)->createTraderOrder('dmcc');

        TraderOrderScenario::of($traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::ContractSigned);

        (new ProcessDmccSellingCommodityToCustomerOrder($traderOrder->id))->handle();

        $this->assertTrue($traderOrder->hasMedia(TraderOrderMediaCollection::SellingCommodityToCustomer));

        $this->assertTrue($traderOrder->checkOrderHistoryAction(FinancingOrderHistory::CreateSellingCommodityToCustomerDocument));
    }

    /**
     * @throws \Throwable
     */
    public function test_job_process_if_the_active_trader_order_has_fake_as_provider_will_work()
    {
        Storage::fake();
        Event::fake();
        /** @var TraderOrder $traderOrder */
        $traderOrder = InProgressOrder::of(self::$order)->createTraderOrder('fake');

        TraderOrderScenario::of($traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::ContractSigned);

        (new ProcessDmccSellingCommodityToCustomerOrder($traderOrder->id))->handle();

        $this->assertTrue($traderOrder->hasMedia(TraderOrderMediaCollection::SellingCommodityToCustomer));

        $this->assertTrue($traderOrder->checkOrderHistoryAction(FinancingOrderHistory::CreateSellingCommodityToCustomerDocument));
    }
}
