<?php

namespace Tests\Unit\Traders;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\Role;
use App\Models\Company;
use App\Models\TraderOrder;
use App\Models\User;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\TraderHelperTrait;
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

    private static object $traderHelperTrait;

    public function setUp(): void
    {
        parent::setUp();
        [self::$company] = $this->createCompany('2000', ['company_cr' => '1234567891']);
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'lenderAdmin@bim.com');
        self::$financingOrder = $this->createOrder(self::$company->id, self::$userLender->id, ['status' => FinancingOrderStatus::PendingApproval]);
        self::$traderHelperTrait = $this->getObjectForTrait(TraderHelperTrait::class);
    }

    public function test_trader_helper_create_step_histories_successfully()
    {
        /** @var TraderOrder $traderOrder */
        $traderOrder = self::$traderHelperTrait->createTraderOrder(self::$financingOrder, '123', 'dmcc');
        $trader = Trader::driver($traderOrder->provider);

        foreach (self::$traderHelperTrait->stepToHistoriesMap as $status => $history) {
            $traderOrder->traderHistories()->delete();
            $filteredHistory = array_filter($history);
            $collectionNames = [];
            $uploadedFiles = [];
            foreach ($filteredHistory as $files) {
                $uploadedFiles = array_merge([$files['file'] => UploadedFile::fake()->create('test.pdf')], $uploadedFiles);
                $collectionNames[] = $files['collection'];
            }

            $request = Request::create('test',
                'POST',
                [],
                [],
                $uploadedFiles
            );

            self::$traderHelperTrait->createStepHistories($request, $trader, $traderOrder, $status);

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
        $traderOrder = self::$traderHelperTrait->createTraderOrder(self::$financingOrder, '123', 'dmcc');

        self::$traderHelperTrait->storeOrderDocumentAsPdf('selling-commodity-to-customer',
            [
                'reference_number' => $traderOrder->reference,
                'company_name' => self::$financingOrder->company->name,
                'order_number' => self::$financingOrder->jd,
                'amount' => '2000',
                'hs_code_description' => 'test product',
                'uom' => 'MTT',
                'quantity' => '1000',
                'warehouse' => 'warehouse',
                'new_owner' => 'owner',
                'date' => now()->toDateString(),
                'time' => now()->toTimeString(),
            ],
            $traderOrder,
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
