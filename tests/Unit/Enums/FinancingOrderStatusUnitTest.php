<?php

namespace Tests\Unit\Enums;

use App\Enums\FinancingOrderStatus;
use Tests\TestCase;

class FinancingOrderStatusUnitTest extends TestCase
{
    public function test_can_move_from_pending_approval_to_approved()
    {
        $pendingApproval = FinancingOrderStatus::fromValue(FinancingOrderStatus::PendingApproval);
        $approved = FinancingOrderStatus::fromValue(FinancingOrderStatus::Approved);
        $this->assertTrue($pendingApproval->canMoveTo($approved));
    }

    public function test_can_move_from_pending_approval_to_rejected()
    {
        $pendingApproval = FinancingOrderStatus::fromValue(FinancingOrderStatus::PendingApproval);
        $rejected = FinancingOrderStatus::fromValue(FinancingOrderStatus::Rejected);
        $this->assertTrue($pendingApproval->canMoveTo($rejected));
    }

    public function test_can_move_from_pending_cancellation_to_cancelled()
    {
        $pendingCancellation = FinancingOrderStatus::fromValue(FinancingOrderStatus::PendingCancellation);
        $cancelled = FinancingOrderStatus::fromValue(FinancingOrderStatus::Cancelled);
        $this->assertTrue($pendingCancellation->canMoveTo($cancelled));
    }

    public function test_can_move_from_rejected_to_pending_cancellation()
    {
        $rejected = FinancingOrderStatus::fromValue(FinancingOrderStatus::Rejected);
        $pendingCancellation = FinancingOrderStatus::fromValue(FinancingOrderStatus::PendingCancellation);
        $this->assertTrue($rejected->canMoveTo($pendingCancellation));
    }

    public function test_can_move_from_approved_to_pending_cancellation()
    {
        $approved = FinancingOrderStatus::fromValue(FinancingOrderStatus::Approved);
        $pendingCancellation = FinancingOrderStatus::fromValue(FinancingOrderStatus::PendingCancellation);
        $this->assertTrue($approved->canMoveTo($pendingCancellation));
    }

    public function test_can_move_from_pending_approval_to_pending_cancellation()
    {
        $pendingApproval = FinancingOrderStatus::fromValue(FinancingOrderStatus::PendingApproval);
        $pendingCancellation = FinancingOrderStatus::fromValue(FinancingOrderStatus::PendingCancellation);
        $this->assertTrue($pendingApproval->canMoveTo($pendingCancellation));
    }

    public function test_allowed_to_update_statuses()
    {
        $this->assertSame(FinancingOrderStatus::$allowedToUpdateStatuses, [
            FinancingOrderStatus::PendingApproval,
            FinancingOrderStatus::Rejected,
        ]);
    }
}
