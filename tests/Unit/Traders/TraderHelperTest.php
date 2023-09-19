<?php

namespace Tests\Unit\Traders;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\Role;
use App\Models\Company;
use App\Models\TraderOrder;
use App\Models\User;
use App\Support\DataTransferObjects\CommodityProductDto;
use App\Support\Traders\TradingStrategies\Dmcc\DmccStrategyV1;
use App\Support\Traders\Traits\TraderHelperTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class TraderHelperTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    private static Company $company;

    private static User $userLender;

    private static Builder|Model $financingOrder;

    private static TraderOrder $traderOrder;

    private static object $traderHelperTrait;

    public function setUp(): void
    {
        parent::setUp();
        [self::$company] = $this->createCompany('2000', ['company_cr' => '1234567891']);
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'lenderAdmin@bim.com');
        self::$financingOrder = $this->createOrder(self::$company->id, self::$userLender->id, ['status' => FinancingOrderStatus::PendingApproval]);
        self::$traderHelperTrait = $this->getObjectForTrait(TraderHelperTrait::class, traitClassName: DmccStrategyV1::class);

        self::$traderOrder = self::$traderHelperTrait->createTraderOrder(self::$financingOrder, '123', 'dmcc');
        $data = [
            'products' => [
                [
                    'product' => 'Yogurt',
                    'quantity' => '10',
                    'amount' => '1000',
                    'currency' => 'SAR',
                    'warehouse' => 'Warehouse',
                    'owner' => 'Owner 1',
                    'previous_owner' => 'Owner 0',
                    'new_owner' => 'Owner 1',
                    'date_time_of_purchasing_commodity' => '2023-01-01 00:00:00',
                    'warehouse_or_vault_emirates' => 'Emirates',
                    'warehouse_or_vault_country' => 'Saudi Arabia',
                    'inventory_record_id' => '1000',
                    'warrant_percentage' => '100',
                    'warrant_no' => '658',
                    'hs_code' => '#234',
                    'uom' => 'Kilo',
                ],
                [
                    'product' => 'Yogurt 2',
                    'quantity' => '5',
                    'amount' => '500',
                    'currency' => 'SAR',
                    'warehouse' => 'Warehouse',
                    'owner' => 'Owner 1',
                    'previous_owner' => 'Owner 2',
                    'new_owner' => 'Owner 3',
                    'date_time_of_purchasing_commodity' => '2023-02-01 00:00:00',
                    'warehouse_or_vault_emirates' => 'Emirates',
                    'warehouse_or_vault_country' => 'Saudi Arabia',
                    'inventory_record_id' => '1000',
                    'warrant_percentage' => '100',
                    'warrant_no' => '658',
                    'hs_code' => '#234',
                    'uom' => 'Kilo',
                ],
            ],
        ];

        $data['exchange_rate'] = '3.75';

        self::$traderOrder->update($data);
        self::$traderOrder->refresh();
    }

    public function test_trader_helper_create_step_histories_successfully()
    {
        /** @var TraderOrder $traderOrder */
        $traderOrder = self::$traderHelperTrait->createTraderOrder(self::$financingOrder, '123', 'dmcc');

        foreach (self::$traderHelperTrait->stepToHistoriesMap as $status => $history) {
            $traderOrder->traderHistories()->delete();
            $filteredHistory = array_filter($history);
            $collectionNames = [];
            $uploadedFiles = [];
            foreach ($filteredHistory as $files) {
                $uploadedFiles = array_merge([$files['file'] => UploadedFile::fake()->create('test.pdf')], $uploadedFiles);
                $collectionNames[] = $files['collection'];
            }

            $request = Request::create(
                'test',
                'POST',
                [],
                [],
                $uploadedFiles
            );

            self::$traderHelperTrait->createStepHistories($request, $traderOrder, $status);

            foreach ($collectionNames as $collection) {
                $this->assertTrue($traderOrder->fresh()->hasMedia($collection));
            }

            $this->assertEquals(count($history), $traderOrder->traderHistories()->whereIn('action', array_keys($history))->count());
        }
    }

    public function test_trader_helper_create_trader_order_successfully()
    {
        $count = self::$financingOrder->traderOrders()->count();
        self::$traderHelperTrait->createTraderOrder(self::$financingOrder, '123', 'dmcc');

        self::assertEquals($count + 1, self::$financingOrder->traderOrders()->count());
    }

    public function test_trader_helper_update_order_status_successfully()
    {
        self::$traderHelperTrait->updateOrderStatus(self::$financingOrder, FinancingOrderStatus::Approved);

        self::assertTrue(self::$financingOrder->status->is(FinancingOrderStatus::Approved));
    }

    public function test_trader_helper_create_trader_order_history_successfully()
    {
        $traderOrder = self::$traderHelperTrait->createTraderOrder(self::$financingOrder, '123', 'dmcc');
        $count = $traderOrder->traderHistories()->count();

        self::$traderHelperTrait->createTraderOrderHistory($traderOrder, FinancingOrderHistory::GetTtiId);

        $this->assertEquals($count + 1, $traderOrder->traderHistories()->count());
    }

    public function test_trader_helper_store_order_document_as_pdf_successfully()
    {
        $separator = ' و ';
        $dateTime = now();

        $products = collect(self::$traderOrder->products);

        $amount = self::$traderOrder->order->selling_price->formatByDecimal();

        $customerName = self::$traderOrder->order->customer_name;
        $productName = $products->pluck('product')->implode($separator);

        self::$traderHelperTrait->storeOrderDocumentAsPdf(
            'selling-commodity-to-customer',
            [
                'reference_number' => self::$traderOrder->id,
                'company_name' => self::$traderOrder->order->company->name,
                'order_number' => self::$traderOrder->financing_order_id,
                'products' => CommodityProductDto::fromArray(self::$traderOrder->products[0]),
                'amount' => $amount,
                'product_name' => $productName,
                'customer_name' => $customerName,
                'contract_signed_date' => $dateTime->toDateString(),
                'contract_signed_time' => $dateTime->toTimeString(),
            ],
            self::$traderOrder,
            TraderOrderMediaCollection::SellingCommodityToCustomer,
        );

        $this->assertNotNull(self::$financingOrder->getFirstMediaUrl(TraderOrderMediaCollection::SellingCommodityToCustomer));
    }

    public function test_trader_helper_attach_document_to_order_successfully()
    {
        $traderOrder = self::$traderHelperTrait->createTraderOrder(self::$financingOrder, '123', 'dmcc');

        self::$traderHelperTrait->attachDocumentToOrder(
            $traderOrder,
            base64_encode('document'),
            TraderOrderMediaCollection::PromiseToPurchase,
            'base64'
        );

        $this->assertNotNull(self::$financingOrder->getFirstMediaUrl(TraderOrderMediaCollection::PromiseToPurchase));
    }
}
