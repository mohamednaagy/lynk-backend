<?php

namespace Tests\Unit\Jobs\General;

use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\TraderOrderStatus;
use App\Jobs\Dmcc\ProcessDmccContractSignedOrder;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\User;
use CodeDredd\Soap\Facades\Soap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class ProcessDmccContractSignedOrderTest extends TestCase
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

        self::$order = $this->createOrder(self::$company->id, self::$lender->id, [
            'status' => FinancingOrderStatus::ContractSigned,
        ]);

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
    public function test_job_process_if_order_status_isnt_contract_signed_will_not_work()
    {
        self::$order->traderOrders()->create([
            'provider' => 'dmcc',
            'reference' => 123,
            'status' => TraderOrderStatus::InProgress,
        ]);

        $statuses = FinancingOrderStatus::getValues();

        foreach ($statuses as $status) {
            if ($status != FinancingOrderStatus::ContractSigned) {
                self::$order->update(['status' => $status]);

                $processOrder = new ProcessDmccContractSignedOrder(self::$order->id);
                $processOrder->handle();
                self::$order = self::$order->fresh();

                $this->assertNull(self::$order->media->first());

                $this->assertTrue(self::$order->status->is($status));
            }
        }
    }

    /**
     * @throws \Throwable
     */
    public function test_job_process_if_the_active_trader_order_has_dmcc_as_provider_will_work()
    {
        Storage::fake();

        /** @var TraderOrder $traderOrder */
        $traderOrder = self::$order->traderOrders()->create([
            'provider' => 'dmcc',
            'reference' => 123,
            'status' => TraderOrderStatus::InProgress,
            'product' => 'Product',
            'quantity' => 2,
            'amount' => '1000 SAR',
            'warehouse' => 'Warehouse ID',
            'owner' => 'Owner 1',
            'previousOwner' => 'Owner 0',
            'newOwner' => 'Owner 1',
            'dateTimeOfPurchasingCommodity' => now()->format('d/m/Y H:i A'),
            'warehouseOrVaultEmirates' => 'Vaault',
            'warehouseOrVaultCountry' => 'Saudi Arabia',
        ]);

        $processOrder = new ProcessDmccContractSignedOrder(self::$order->id);
        $processOrder->handle();
        self::$order = self::$order->fresh();

        $this->assertNotNull($traderOrder->getFirstMediaUrl(TraderOrderMediaCollection::SellingCommodityToCustomer));

        $this->assertTrue(self::$order->status->is(FinancingOrderStatus::CommoditySoldToCustomer));
    }

    /**
     * @throws \Throwable
     */
    public function test_job_process_if_the_active_trader_order_has_fake_as_provider_will_work()
    {
        Storage::fake();

        /** @var TraderOrder $traderOrder */
        $traderOrder = self::$order->traderOrders()->create([
            'provider' => 'fake',
            'reference' => 123,
            'status' => TraderOrderStatus::InProgress,
        ]);

        $processOrder = new ProcessDmccContractSignedOrder(self::$order->id);
        $processOrder->handle();
        self::$order = self::$order->fresh();

        $this->assertTrue(self::$order->status->is(FinancingOrderStatus::CommoditySoldToCustomer));
        $this->assertNotNull($traderOrder->getFirstMediaUrl(TraderOrderMediaCollection::SellingCommodityToCustomer));
    }
}
