<?php

namespace Jobs\Dmcc\V1;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\Role;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\User;
use App\Support\Traders\Drivers\Dmcc\Jobs\V1\ProcessDmccRespondedToPtpOrder;
use CodeDredd\Soap\Facades\Soap;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\Support\FinancingOrders\TraderOrderScenario;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class ProcessDmccRespondedToPtpOrderTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    protected static Company $company;

    protected static User $lender;

    protected static FinancingOrder $order;

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany();
        self::$lender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$order = OrderScenario::inProgress()
            ->lender(self::$company)
            ->creator(self::$lender)
            ->commit()
            ->model();
    }

    public function test_process_dmcc_responded_to_ptp_order_with_dmcc_driver_success()
    {
        Storage::fake();
        Soap::fake(function () {
            return Soap::response([
                'getdocument' => [
                    [
                        'getDocumentByTypeResponse' => [
                            [
                                'document' => 'document',
                            ],
                        ],
                    ],
                ],
            ]);
        });

        /** @var TraderOrder $traderOrder */
        $traderOrder = InProgressOrder::of(self::$order)->createTraderOrder('dmcc');
        TraderOrderScenario::of($traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::RespondPtp);

        (new ProcessDmccRespondedToPtpOrder($traderOrder->id))->handle();

        $this->assertNotNull($traderOrder->getFirstMediaUrl(TraderOrderMediaCollection::PromiseToPurchase));
        $this->assertNotNull($traderOrder->getFirstMediaUrl(TraderOrderMediaCollection::TtiHoldingCertificate));

        $firstTraderHistory = $traderOrder->traderHistories()->skip(2)->first();
        $secondTraderHistory = $traderOrder->traderHistories()->skip(3)->first();
        $thirdTraderHistory = $traderOrder->traderHistories()->skip(4)->first();
        $fourthTraderHistory = $traderOrder->traderHistories()->skip(5)->first();

        $this->assertEquals(FinancingOrderHistory::GetPtpDocument, $firstTraderHistory->action);
        $this->assertEquals(FinancingOrderHistory::AttachPtpDocumentToOrder, $secondTraderHistory->action);
        $this->assertEquals(FinancingOrderHistory::GetTtiHoldingCertificateDocument, $thirdTraderHistory->action);
        $this->assertEquals(FinancingOrderHistory::AttachTtiHoldingCertificateDocument, $fourthTraderHistory->action);
    }

    public function test_process_dmcc_responded_to_ptp_order_with_fake_driver_sucess()
    {
        Storage::fake();
        Http::fake(function () {
            return Http::response([
                'data' => [
                    'fileContent' => 'document',
                ],
            ], 200);
        });

        /** @var TraderOrder $traderOrder */
        $traderOrder = InProgressOrder::of(self::$order)->createTraderOrder('fake');
        TraderOrderScenario::of($traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::RespondPtp);

        (new ProcessDmccRespondedToPtpOrder($traderOrder->id))->handle();

        $this->assertNotNull($traderOrder->getFirstMediaUrl(TraderOrderMediaCollection::PromiseToPurchase));
        $this->assertNotNull($traderOrder->getFirstMediaUrl(TraderOrderMediaCollection::TtiHoldingCertificate));

        $firstTraderHistory = $traderOrder->traderHistories()->skip(2)->first();
        $secondTraderHistory = $traderOrder->traderHistories()->skip(3)->first();
        $thirdTraderHistory = $traderOrder->traderHistories()->skip(4)->first();
        $fourthTraderHistory = $traderOrder->traderHistories()->skip(5)->first();

        $this->assertEquals(FinancingOrderHistory::GetPtpDocument, $firstTraderHistory->action);
        $this->assertEquals(FinancingOrderHistory::AttachPtpDocumentToOrder, $secondTraderHistory->action);
        $this->assertEquals(FinancingOrderHistory::GetTtiHoldingCertificateDocument, $thirdTraderHistory->action);
        $this->assertEquals(FinancingOrderHistory::AttachTtiHoldingCertificateDocument, $fourthTraderHistory->action);
    }

    public function test_process_dmcc_responded_to_ptp_order_with_not_valid_statuses_fail()
    {
        Storage::fake();
        Http::fake(function () {
            return Http::response([
                'data' => [
                    'fileContent' => 'document',
                ],
            ], 200);
        });

        /** @var TraderOrder $traderOrder */
        $traderOrder = InProgressOrder::of(self::$order)->createTraderOrder('fake');

        $financeHistories = FinancingOrderHistory::asArray();

        foreach ($financeHistories as $financeHistory) {
            if (in_array($financeHistory, [
                FinancingOrderHistory::RespondPtp, FinancingOrderHistory::GetTtiId, FinancingOrderHistory::OrderCancelled, FinancingOrderHistory::Expired,
            ])) {
                continue;
            }

            TraderOrderScenario::of($traderOrder)
                ->reset()
                ->moveToHistory($financeHistory);

            (new ProcessDmccRespondedToPtpOrder($traderOrder->id))->handle();
            $this->assertTrue($traderOrder->doesLastActionMatchWith($financeHistory));
        }
    }
}
