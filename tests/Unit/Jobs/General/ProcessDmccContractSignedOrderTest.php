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
use Tests\Traits\InteractsWithLender;

class ProcessDmccContractSignedOrderTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    protected static Company $company;

    protected static User $lender;

    protected static FinancingOrder $order;

    protected static $fakeFileDmcc;

    public function setUp(): void
    {
        parent::setUp();
        [self::$company] = $this->createCompany();
        self::$lender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$order = $this->createOrder(self::$company->id, self::$lender->id, [
            'status' => FinancingOrderStatus::ContractSigned,
        ]);
        self::$fakeFileDmcc = Http::fake(function () {
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
    public function test_job_process_if_order_status_isnt_contracr_signed_will_nothing_work()
    {
        self::$fakeFileDmcc;
        self::$order->traderOrders()->create([
            'provider' => 'dmcc',
            'reference' => 123,
            'status' => TraderOrderStatus::InProgress,
        ]);

        $statuses = FinancingOrderStatus::getValues();
        foreach ($statuses as $status) {
            if ($status != 10) {
                self::$order->update(['status' => $status]);

                $processOrder = new ProcessDmccContractSignedOrder(self::$order->id);
                $processOrder->handle();
                self::$order = self::$order->fresh();
            }
        }

        $this->assertNull(self::$order->media->first());

        $this->assertFalse(self::$order->status->is(FinancingOrderStatus::CommoditySoldToCustomer));
    }

    /**
     * @throws \Throwable
     */
    public function test_job_process_if_the_active_trader_order_has_dmcc_as_provider_will_successful_work()
    {
        self::$fakeFileDmcc;

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
    public function test_job_process_if_the_active_trader_order_has_fake_as_provider_will_successful_work()
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

        $this->assertNotNull(self::$order->getFirstMediaUrl(FinancingOrderMediaCollection::SellingCommodityToCustomer));
    }

    /**
     * @throws \Throwable
     */
    public function test_job_process_if_the_current_order_status_is_contracr_signed_and_trader_order_has_fake_as_provider_will_successful_work()
    {
        Storage::fake();
        self::$order->traderOrders()->create([
            'provider' => 'fake',
            'reference' => 123,
            'status' => TraderOrderStatus::InProgress,
        ]);
        $this->assertTrue(self::$order->status->is(FinancingOrderStatus::ContractSigned));

        $processOrder = new ProcessDmccContractSignedOrder(self::$order->id);
        $processOrder->handle();
        self::$order = self::$order->fresh();

        $this->assertNotNull(self::$order->getFirstMediaUrl(FinancingOrderMediaCollection::SellingCommodityToCustomer));
    }

    /**
     * @throws \Throwable
     */
    public function test_job_process_if_the_current_order_status_is_contracr_signed_and_trader_order_has_dmcc_as_provider_will_successful_work()
    {
        self::$fakeFileDmcc;
        self::$order->traderOrders()->create([
            'provider' => 'dmcc',
            'reference' => 123,
            'status' => TraderOrderStatus::InProgress,
        ]);

        $processOrder = new ProcessDmccContractSignedOrder(self::$order->id);
        $processOrder->handle();
        self::$order = self::$order->fresh();

        $this->assertNotNull(self::$order->getFirstMediaUrl(FinancingOrderMediaCollection::SellingCommodityToCustomer));
    }

    /**
     * @throws \Throwable
     */
    public function test_selling_commodity_to_customer_document_will_genrated_if_trader_order_has_fake_as_provider_will_successful_work()
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

        $this->assertFileExists(self::$order->media->first()->getPath());
    }

    /**
     * @throws \Throwable
     */
    public function test_selling_commodity_to_customer_document_will_genrated_if_trader_order_has_dmcc_as_provider_will_successful_work()
    {
        self::$fakeFileDmcc;
        self::$order->traderOrders()->create([
            'provider' => 'dmcc',
            'reference' => 123,
            'status' => TraderOrderStatus::InProgress,
        ]);

        $processOrder = new ProcessDmccContractSignedOrder(self::$order->id);
        $processOrder->handle();
        self::$order = self::$order->fresh();

        $this->assertFileExists(self::$order->media->first()->getPath());
    }

    /**
     * @throws \Throwable
     */
    public function test_the_order_status_is_update_to_commodity_sold_to_customer_and_trader_order_has_fake_as_provider_will_successful_work()
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
    }

    /**
     * @throws \Throwable
     */
    public function test_the_order_status_is_update_to_commodity_sold_to_customer_and_trader_order_has_dmcc_as_provider_will_successful_work()
    {
        self::$fakeFileDmcc;
        self::$order->traderOrders()->create([
            'provider' => 'dmcc',
            'reference' => 123,
            'status' => TraderOrderStatus::InProgress,
        ]);

        $processOrder = new ProcessDmccContractSignedOrder(self::$order->id);
        $processOrder->handle();
        self::$order = self::$order->fresh();

        $this->assertTrue(self::$order->status->is(FinancingOrderStatus::CommoditySoldToCustomer));
    }
}
