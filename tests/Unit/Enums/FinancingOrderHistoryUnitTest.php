<?php

namespace Enums;

use App\Enums\FinancingOrderHistory;
use Tests\TestCase;

class FinancingOrderHistoryUnitTest extends TestCase
{
    public function test_not_cancelable_actions()
    {
        $this->assertSame(FinancingOrderHistory::$notCancelableActions, [
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
}
