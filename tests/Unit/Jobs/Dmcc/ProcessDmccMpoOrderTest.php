<?php

namespace Jobs\Dmcc;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\Role;
use App\Enums\TraderOrderStatus;
use App\Jobs\Dmcc\ProcessDmccMpoOrder;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\Media;
use App\Models\TraderHistory;
use App\Models\TraderOrder;
use App\Models\User;
use CodeDredd\Soap\Facades\Soap;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\Support\FinancingOrders\TraderOrderScenario;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class ProcessDmccMpoOrderTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    protected static Company $company;

    protected static User $lender;

    protected static FinancingOrder $order;

    protected static Model|TraderOrder $traderOrder;

    /**
     * @throws BindingResolutionException
     */
    protected function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany();
        self::$lender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);

        self::$order = OrderScenario::inProgress()
            ->lender(self::$company)
            ->creator(self::$lender)
            ->commit()
            ->model();

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

        self::$traderOrder = InProgressOrder::of(self::$order)->createTraderOrder('dmcc', data: $data);

        TraderOrderScenario::of(self::$traderOrder)
            ->moveToHistory(FinancingOrderHistory::CreateSellingCommodityToCustomerDocument);

        Soap::fake(function () {
            return Soap::response([
                'successCode' => '0000',
                'versionNo' => 1,
                'getdocument' => [
                    [
                        'getDocumentByTypeResponse' => [
                            ['document' => 'document'],
                        ],
                    ],
                ],
            ]);
        });

        Http::fake(function () {
            return Http::response([
                'data' => [
                    'fileContent' => 'document',
                ],
            ], 200);
        });
    }

    public function test_process_dmcc_mpo_with_dmcc_as_trader_will_success()
    {
        (new ProcessDmccMpoOrder(self::$traderOrder->id))->handle();

        $this->assertTrue(self::$traderOrder->doesLastActionMatchWith(FinancingOrderHistory::AttachMpoDocument));
    }

    public function test_process_dmcc_mpo_with_fake_as_trader_order_will_success()
    {
        self::$traderOrder->update([
            'provider' => 'fake',
        ]);
        (new ProcessDmccMpoOrder(self::$traderOrder->id))->handle();

        $this->assertTrue(self::$traderOrder->doesLastActionMatchWith(FinancingOrderHistory::AttachMpoDocument));
    }

    public function test_process_dmcc_mpo_with_not_supported_trader_will_fail()
    {
        self::$traderOrder->update([
            'provider' => 'else',
        ]);

        $this->expectException(InvalidArgumentException::class);

        (new ProcessDmccMpoOrder(self::$traderOrder->id))->handle();

        $this->assertTrue(self::$traderOrder->doesLastActionMatchWith(FinancingOrderHistory::CreateSellingCommodityToCustomerDocument));
    }

    public function test_process_dmcc_mpo_when_order_status_not_client_wakala_complete_fail()
    {
        $financeHistories = FinancingOrderHistory::asArray();

        foreach ($financeHistories as $financeHistory) {
            if (in_array($financeHistory, [
                FinancingOrderHistory::CreateSellingCommodityToCustomerDocument, FinancingOrderHistory::GetTtiId, FinancingOrderHistory::OrderCancelled, FinancingOrderHistory::Expired,
            ])) {
                continue;
            }

            self::$traderOrder->update([
                'status' => TraderOrderStatus::InProgress,
            ]);

            TraderOrderScenario::of(self::$traderOrder)
                ->reset()
                ->moveToHistory($financeHistory);

            (new ProcessDmccMpoOrder(self::$traderOrder->id))->handle();

            $this->assertTrue(self::$traderOrder->doesLastActionMatchWith($financeHistory));
        }
    }

    public function test_process_dmcc_mpo_histories_created()
    {
        Storage::fake();
        UploadedFile::fake();

        $traderHistories = TraderHistory::query()->count();
        $media = Media::query()->count();

        (new ProcessDmccMpoOrder(self::$traderOrder->id))->handle();

        $this->assertDatabaseCount((new TraderHistory())->getTable(), $traderHistories + 3);
        $this->assertDatabaseHas((new TraderHistory())->getTable(), [
            'trader_order_id' => self::$traderOrder->id,
            'action' => FinancingOrderHistory::IssueMurabahaOffer,
        ]);
        $this->assertDatabaseHas((new TraderHistory())->getTable(), [
            'trader_order_id' => self::$traderOrder->id,
            'action' => FinancingOrderHistory::GetMurabahaPurchaseOfferDocument,
        ]);
        $this->assertDatabaseHas((new TraderHistory())->getTable(), [
            'trader_order_id' => self::$traderOrder->id,
            'action' => FinancingOrderHistory::AttachMpoDocument,
        ]);

        $this->assertDatabaseCount((new Media())->getTable(), $media + 1);

        $this->assertDatabaseHas((new Media())->getTable(), [
            'model_id' => self::$traderOrder->id,
            'model_type' => (new TraderOrder)->getMorphClass(),
            'collection_name' => TraderOrderMediaCollection::MurabahaPurchaseOrder,
        ]);

        $this->assertNotNull(self::$traderOrder->getFirstMediaUrl(TraderOrderMediaCollection::MurabahaPurchaseOrder));
    }
}
