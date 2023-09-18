<?php

namespace Tests\Unit\Traders;

use App\Enums\Area;
use App\Enums\CancelTraderOrderStatus;
use App\Enums\FinancingOrderHistory;
use App\Enums\MurabhaStep;
use App\Enums\TraderOrderMode;
use App\Enums\TraderOrderStatus;
use App\Jobs\General\ProcessAskClientForWakala;
use App\Models\TraderOrder;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamBidCertificate;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamOrderResultNYY;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamOrderResultYNN;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamOtcCertificate;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamSellingCommodityToOpenMarket;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamSellingCommodityToOpenMarketForCancellation;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamStbCertificate;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamStbCertificateAfterCancellation;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamTransferOwnershipToCustomer;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamTransferOwnershipToLender;
use App\Support\Traders\Drivers\Bursam\Strategies\BursamV2Driver;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\Support\FinancingOrders\TraderOrderScenario;

class BursamV2DriverTest extends BursamV1DriverTest
{
    protected static string $driverClass = BursamV2Driver::class;

    protected static string $version = 'v2';

    public function test_get_or_initiate_trader_oder_if_has_trader_order_success()
    {
        self::$traderOrder->update(['status' => TraderOrderStatus::Initiated]);
        $traderOrderCount = TraderOrder::query()->count();

        $traderOrder = self::$driver->getOrInitiateTraderOrder(self::$order);

        $this->assertDatabaseCount((new TraderOrder())->getTable(), $traderOrderCount);
        $this->assertEquals($traderOrder->id, self::$traderOrder->id);
    }

    public function test_get_or_initiate_trader_order_if_has_no_trader_order_success()
    {
        $traderOrderCount = TraderOrder::query()->count();

        $order = OrderScenario::inProgress()
            ->lender(self::$company)
            ->creator(self::$lender)
            ->commit()
            ->model();

        $traderOrder = self::$driver->getOrInitiateTraderOrder($order);

        $this->assertDatabaseCount((new TraderOrder())->getTable(), $traderOrderCount + 1);
        $this->assertInstanceOf(TraderOrder::class, $traderOrder);
    }

    public function test_cancel_trader_order_manual_mode()
    {
        Event::fake();
        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::CommoditySoldToMarket);

        $result = self::$driver->cancelTraderOrder(self::$traderOrder);

        $this->assertEquals(CancelTraderOrderStatus::Cancelled, $result);
        $this->assertTrue(self::$traderOrder->status->is(TraderOrderStatus::Cancelled));
    }

    public function test_cancel_trader_order_if_commodity_purchased()
    {
        Bus::fake();
        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::AttachTtiHoldingCertificateDocument);

        $result = self::$driver->cancelTraderOrder(self::$traderOrder);

        $this->assertEquals(CancelTraderOrderStatus::PendingCancellation, $result);
        $this->assertTrue(self::$traderOrder->status->is(TraderOrderStatus::PendingCancellation));
        Bus::assertChained([
            ProcessBursamSellingCommodityToOpenMarketForCancellation::class,
            ProcessBursamStbCertificateAfterCancellation::class,
            CallQueuedClosure::class,
        ]);
    }

    public function test_cancel_trader_while_sending_trader_request_to_bursa()
    {
        $this->expectException(\Exception::class);

        TraderOrderScenario::of(self::$traderOrder)
            ->reset();

        self::$driver->cancelTraderOrder(self::$traderOrder);
    }

    public function test_cancel_trader_while_waiting_response_from_bursa()
    {
        $this->expectException(\Exception::class);

        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument);

        self::$driver->cancelTraderOrder(self::$traderOrder);
    }

    public function test_is_trader_order_cancellable_success()
    {
        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::GetTtiHoldingCertificateDocument);

        $this->assertTrue(self::$driver->isTraderOrderCancellable(self::$traderOrder, null));
    }

    public function test_is_trader_order_cancellable_if_in_transition_state_fails()
    {
        TraderOrderScenario::of(self::$traderOrder)
            ->reset();

        $this->assertFalse(self::$driver->isTraderOrderCancellable(self::$traderOrder, null));
    }

    public function test_is_trader_order_cancellable_if_in_contract_signed_state_fails()
    {
        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToStep(MurabhaStep::ContractSigned);

        $this->assertFalse(self::$driver->isTraderOrderCancellable(self::$traderOrder, Area::Lender));
    }

    public function test_dispatch_job_for_transitioning_flow_if_trading_mode_automatic_success()
    {
        Queue::fake();
        self::$traderOrder->update([
            'mode' => TraderOrderMode::Automatic,
        ]);

        self::$traderOrder = TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->getTraderOrder();

        self::$driver->dispatchJobForTransitioningFlow(self::$traderOrder);

        Queue::assertPushed(ProcessBursamOrderResultYNN::class);

        self::$traderOrder = TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::GetTtiHoldingCertificateDocument)
            ->getTraderOrder();

        self::$driver->dispatchJobForTransitioningFlow(self::$traderOrder);

        Queue::assertPushed(ProcessBursamBidCertificate::class);

        self::$traderOrder = TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::AttachTtiHoldingCertificateDocument)
            ->getTraderOrder();

        self::$driver->dispatchJobForTransitioningFlow(self::$traderOrder);

        Queue::assertPushed(ProcessBursamTransferOwnershipToLender::class);

        self::$traderOrder = TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::ContractSigned)
            ->getTraderOrder();

        self::$driver->dispatchJobForTransitioningFlow(self::$traderOrder);

        Queue::assertPushed(ProcessBursamTransferOwnershipToCustomer::class);

        self::$traderOrder = TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::CreateSellingCommodityToCustomerDocument)
            ->getTraderOrder();

        self::$driver->dispatchJobForTransitioningFlow(self::$traderOrder);

        Queue::assertPushed(ProcessAskClientForWakala::class);

        self::$traderOrder = TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::ClientWakalaAccepted)
            ->getTraderOrder();

        self::$driver->dispatchJobForTransitioningFlow(self::$traderOrder);

        Queue::assertPushed(ProcessBursamSellingCommodityToOpenMarket::class);

        self::$traderOrder = TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument)
            ->getTraderOrder();

        self::$driver->dispatchJobForTransitioningFlow(self::$traderOrder);

        Queue::assertPushed(ProcessBursamOrderResultNYY::class);

        self::$traderOrder = TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::CommoditySoldToMarket)
            ->getTraderOrder();

        self::$driver->dispatchJobForTransitioningFlow(self::$traderOrder);

        Queue::assertPushed(ProcessBursamOtcCertificate::class);

        self::$traderOrder = TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToHistory(FinancingOrderHistory::GetOwnershipToCustomerCertificate)
            ->getTraderOrder();

        self::$driver->dispatchJobForTransitioningFlow(self::$traderOrder);

        Queue::assertPushed(ProcessBursamStbCertificate::class);
    }
}
