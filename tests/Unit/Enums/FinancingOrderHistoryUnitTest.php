<?php

namespace Enums;

use App\Enums\FinancingOrderHistory;
use Tests\TestCase;

class FinancingOrderHistoryUnitTest extends TestCase
{
    public function test_not_cancellable_actions()
    {
        $this->assertSame(FinancingOrderHistory::$notCancellableActions, [
            FinancingOrderHistory::GetMurabahaPurchaseOfferDocument,
            FinancingOrderHistory::AttachMpoDocument,
            FinancingOrderHistory::IssueMurabahaOffer,
            FinancingOrderHistory::MurabahaSaleCompleted,
            FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument,
            FinancingOrderHistory::ContractSigned,
            FinancingOrderHistory::AttachWarrantAmendmentExceptWarrantNoDocument,
            FinancingOrderHistory::OrderCancelled,
        ]);
    }

    public function test_is_trader_order_cancellable()
    {
        $cancellable = collect(FinancingOrderHistory::asSelectArray())
            ->except(FinancingOrderHistory::$notCancellableActions)
            ->keys();
        $this->assertFalse(FinancingOrderHistory::isTraderOrderCancellable(FinancingOrderHistory::$notCancellableActions));
        $this->assertTrue(FinancingOrderHistory::isTraderOrderCancellable($cancellable));
    }
}
