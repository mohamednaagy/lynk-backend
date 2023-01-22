<?php

namespace Tests\Unit\Jobs\General;

use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Enums\Role;
use App\Enums\TraderOrderStatus;
use App\Jobs\Dmcc\ProcessDmccContractSignedOrder;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
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

        self::$lender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);

        self::$order = $this->createOrder(self::$company->id, self::$lender->id, [
            'status' => FinancingOrderStatus::ContractSigned,
        ]);

        Http::fake(function () {
            return Http::response([
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
            ], 200);
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

        self::$order->traderOrders()->create([
            'provider' => 'dmcc',
            'reference' => 123,
            'status' => TraderOrderStatus::InProgress,
        ]);

        $processOrder = new ProcessDmccContractSignedOrder(self::$order->id);
        $processOrder->handle();
        self::$order = self::$order->fresh();

        $this->assertNotNull(self::$order->getFirstMediaUrl(FinancingOrderMediaCollection::SellingCommodityToCustomer));

        $this->assertTrue(self::$order->status->is(FinancingOrderStatus::CommoditySoldToCustomer));
    }

    /**
     * @throws \Throwable
     */
    public function test_job_process_if_the_active_trader_order_has_fake_as_provider_will_work()
    {
        Storage::fake();

        self::$order->traderOrders()->create([
            'provider' => 'fake',
            'reference' => 123,
            'status' => TraderOrderStatus::InProgress,
        ]);

        $processOrder = new ProcessDmccContractSignedOrder(self::$order->id);
        $processOrder->handle();
        self::$order = self::$order->fresh();

        $this->assertTrue(self::$order->status->is(FinancingOrderStatus::CommoditySoldToCustomer));
        $this->assertNotNull(self::$order->getFirstMediaUrl(FinancingOrderMediaCollection::SellingCommodityToCustomer));
    }
}
