<?php

namespace Jobs\General;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\Role;
use App\Enums\TraderOrderStatus;
use App\Jobs\Dmcc\ProcessDmccRespondedToPtpOrder;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\User;
use CodeDredd\Soap\Facades\Soap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class ProcessDmccRespondedToPtpOrderTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    protected static Company $company;

    protected static User $lender;

    protected static FinancingOrder $order;

    public function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany();
        self::$lender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$order = $this->createOrder(self::$company->id, self::$lender->id, [
            'status' => FinancingOrderStatus::RespondedToPtp,
        ]);
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
        $traderOrder = self::$order->traderOrders()->create([
            'provider' => 'dmcc',
            'reference' => '1',
            'status' => TraderOrderStatus::InProgress,
        ]);

        (new ProcessDmccRespondedToPtpOrder(self::$order->id))->handle();

        self::$order = self::$order->fresh();

        $this->assertNotNull($traderOrder->getFirstMediaUrl(TraderOrderMediaCollection::PromiseToPurchase));
        $this->assertNotNull($traderOrder->getFirstMediaUrl(TraderOrderMediaCollection::TtiHoldingCertificate));

        $firstTraderHistory = $traderOrder->traderHistories()->first();
        $secondTraderHistory = $traderOrder->traderHistories()->skip(1)->first();
        $thirdTraderHistory = $traderOrder->traderHistories()->skip(2)->first();
        $fourthTraderHistory = $traderOrder->traderHistories()->skip(3)->first();

        $this->assertEquals(FinancingOrderHistory::GetPtpDocument, $firstTraderHistory->action);
        $this->assertEquals(FinancingOrderHistory::AttachPtpDocumentToOrder, $secondTraderHistory->action);
        $this->assertEquals(FinancingOrderHistory::GetTtiHoldingCertificateDocument, $thirdTraderHistory->action);
        $this->assertEquals(FinancingOrderHistory::AttachTtiHoldingCertificateDocument, $fourthTraderHistory->action);

        $this->assertTrue(self::$order->status->is(FinancingOrderStatus::PtpDocumentRetrieved));
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
        $traderOrder = self::$order->traderOrders()->create([
            'provider' => 'fake',
            'reference' => '1',
            'status' => TraderOrderStatus::InProgress,
        ]);

        (new ProcessDmccRespondedToPtpOrder(self::$order->id))->handle();

        self::$order = self::$order->fresh();

        $this->assertNotNull($traderOrder->getFirstMediaUrl(TraderOrderMediaCollection::PromiseToPurchase));
        $this->assertNotNull($traderOrder->getFirstMediaUrl(TraderOrderMediaCollection::TtiHoldingCertificate));

        $firstTraderHistory = $traderOrder->traderHistories()->first();
        $secondTraderHistory = $traderOrder->traderHistories()->skip(1)->first();
        $thirdTraderHistory = $traderOrder->traderHistories()->skip(2)->first();
        $fourthTraderHistory = $traderOrder->traderHistories()->skip(3)->first();

        $this->assertEquals(FinancingOrderHistory::GetPtpDocument, $firstTraderHistory->action);
        $this->assertEquals(FinancingOrderHistory::AttachPtpDocumentToOrder, $secondTraderHistory->action);
        $this->assertEquals(FinancingOrderHistory::GetTtiHoldingCertificateDocument, $thirdTraderHistory->action);
        $this->assertEquals(FinancingOrderHistory::AttachTtiHoldingCertificateDocument, $fourthTraderHistory->action);

        $this->assertTrue(self::$order->status->is(FinancingOrderStatus::PtpDocumentRetrieved));
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
        $traderOrder = self::$order->traderOrders()->create([
            'provider' => 'fake',
            'reference' => '1',
            'status' => TraderOrderStatus::InProgress,
        ]);

        collect(FinancingOrderStatus::asArray())
            ->except([FinancingOrderStatus::RespondedToPtp])
            ->each(function ($status) {
                self::$order->update(['status' => $status]);
                (new ProcessDmccRespondedToPtpOrder(self::$order->id))->handle();
                $this->assertTrue(self::$order->status->is($status));
            });
    }
}
