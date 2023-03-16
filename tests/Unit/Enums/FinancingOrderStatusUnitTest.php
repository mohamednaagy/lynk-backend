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

    public function test_can_move_from_murabaha_sale_completed_to_completed()
    {
        $murabahaSaleCompleted = FinancingOrderStatus::fromValue(FinancingOrderStatus::MurabahaSaleCompleted);
        $completed = FinancingOrderStatus::fromValue(FinancingOrderStatus::Completed);
        $this->assertTrue($murabahaSaleCompleted->canMoveTo($completed));
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

    public function test_can_move_from_responded_to_ptp_to_pending_cancellation()
    {
        $respondedToPtp = FinancingOrderStatus::fromValue(FinancingOrderStatus::RespondedToPtp);
        $pendingCancellation = FinancingOrderStatus::fromValue(FinancingOrderStatus::PendingCancellation);
        $this->assertTrue($respondedToPtp->canMoveTo($pendingCancellation));
    }

    public function test_can_move_from_pending_approval_to_pending_cancellation()
    {
        $pendingApproval = FinancingOrderStatus::fromValue(FinancingOrderStatus::PendingApproval);
        $pendingCancellation = FinancingOrderStatus::fromValue(FinancingOrderStatus::PendingCancellation);
        $this->assertTrue($pendingApproval->canMoveTo($pendingCancellation));
    }

    public function test_can_move_from_commodity_purchased_to_pending_cancellation()
    {
        $commodityPurchased = FinancingOrderStatus::fromValue(FinancingOrderStatus::CommodityPurchased);
        $pendingCancellation = FinancingOrderStatus::fromValue(FinancingOrderStatus::PendingCancellation);
        $this->assertTrue($commodityPurchased->canMoveTo($pendingCancellation));
    }

    public function test_can_move_from_waiting_client_wakala_to_pending_cancellation()
    {
        $waitingClientWakala = FinancingOrderStatus::fromValue(FinancingOrderStatus::WaitingClientWakala);
        $pendingCancellation = FinancingOrderStatus::fromValue(FinancingOrderStatus::PendingCancellation);
        $this->assertTrue($waitingClientWakala->canMoveTo($pendingCancellation));
    }

    public function test_can_move_from_ptp_document_retrieved_to_pending_cancellation()
    {
        $ptpDocumentRetrieved = FinancingOrderStatus::fromValue(FinancingOrderStatus::PtpDocumentRetrieved);
        $pendingCancellation = FinancingOrderStatus::fromValue(FinancingOrderStatus::PendingCancellation);
        $this->assertTrue($ptpDocumentRetrieved->canMoveTo($pendingCancellation));
    }

    public function test_can_move_from_client_wakala_completed_to_pending_cancellation()
    {
        $clientWakalaCompleted = FinancingOrderStatus::fromValue(FinancingOrderStatus::ClientWakalaCompleted);
        $pendingCancellation = FinancingOrderStatus::fromValue(FinancingOrderStatus::PendingCancellation);
        $this->assertTrue($clientWakalaCompleted->canMoveTo($pendingCancellation));
    }

    public function test_can_move_from_commodity_sold_to_customer_to_pending_cancellation()
    {
        $commoditySoldToCustomer = FinancingOrderStatus::fromValue(FinancingOrderStatus::CommoditySoldToCustomer);
        $pendingCancellation = FinancingOrderStatus::fromValue(FinancingOrderStatus::PendingCancellation);
        $this->assertTrue($commoditySoldToCustomer->canMoveTo($pendingCancellation));
    }

    public function test_can_move_from_waiting_purchasing_commodity_to_pending_cancellation()
    {
        $waitingPurchasingCommodity = FinancingOrderStatus::fromValue(FinancingOrderStatus::WaitingPurchasingCommodity);
        $pendingCancellation = FinancingOrderStatus::fromValue(FinancingOrderStatus::PendingCancellation);
        $this->assertTrue($waitingPurchasingCommodity->canMoveTo($pendingCancellation));
    }

    public function test_can_move_from_approved_to_waiting_purchasing_commodity()
    {
        $approved = FinancingOrderStatus::fromValue(FinancingOrderStatus::Approved);
        $waitingPurchasingCommodity = FinancingOrderStatus::fromValue(FinancingOrderStatus::WaitingPurchasingCommodity);
        $this->assertTrue($approved->canMoveTo($waitingPurchasingCommodity));
    }

    public function test_can_move_from_waiting_client_wakala_to_client_wakala_completed()
    {
        $waitingClientWakala = FinancingOrderStatus::fromValue(FinancingOrderStatus::WaitingClientWakala);
        $clientWakalaCompleted = FinancingOrderStatus::fromValue(FinancingOrderStatus::ClientWakalaCompleted);
        $this->assertTrue($waitingClientWakala->canMoveTo($clientWakalaCompleted));
    }

    public function test_can_move_from_client_wakala_completed_to_waiting_purchasing_commodity()
    {
        $clientWakalaCompleted = FinancingOrderStatus::fromValue(FinancingOrderStatus::ClientWakalaCompleted);
        $waitingPurchasingCommodity = FinancingOrderStatus::fromValue(FinancingOrderStatus::WaitingPurchasingCommodity);
        $this->assertTrue($clientWakalaCompleted->canMoveTo($waitingPurchasingCommodity));
    }

    public function test_can_move_from_waiting_purchasing_commodity_to_responded_to_ptp()
    {
        $waitingPurchasingCommodity = FinancingOrderStatus::fromValue(FinancingOrderStatus::WaitingPurchasingCommodity);
        $respondedToPtp = FinancingOrderStatus::fromValue(FinancingOrderStatus::RespondedToPtp);
        $this->assertTrue($waitingPurchasingCommodity->canMoveTo($respondedToPtp));
    }

    public function test_can_move_from_responded_to_ptp_to_ptp_document_retrieved()
    {
        $respondedToPtp = FinancingOrderStatus::fromValue(FinancingOrderStatus::RespondedToPtp);
        $ptpDocumentRetrieved = FinancingOrderStatus::fromValue(FinancingOrderStatus::PtpDocumentRetrieved);
        $this->assertTrue($respondedToPtp->canMoveTo($ptpDocumentRetrieved));
    }

    public function test_can_move_from_ptp_document_retrieved_to_commodity_purchased()
    {
        $ptpDocumentRetrieved = FinancingOrderStatus::fromValue(FinancingOrderStatus::PtpDocumentRetrieved);
        $commodityPurchased = FinancingOrderStatus::fromValue(FinancingOrderStatus::CommodityPurchased);
        $this->assertTrue($ptpDocumentRetrieved->canMoveTo($commodityPurchased));
    }

    public function test_can_move_from_commodity_purchased_to_contract_signed()
    {
        $commodityPurchased = FinancingOrderStatus::fromValue(FinancingOrderStatus::CommodityPurchased);
        $contractSigned = FinancingOrderStatus::fromValue(FinancingOrderStatus::ContractSigned);
        $this->assertTrue($commodityPurchased->canMoveTo($contractSigned));
    }

    public function test_can_move_from_contract_signed_to_waiting_client_wakala()
    {
        $contractSigned = FinancingOrderStatus::fromValue(FinancingOrderStatus::ContractSigned);
        $commoditySoldToCustomer = FinancingOrderStatus::fromValue(FinancingOrderStatus::WaitingClientWakala);
        $this->assertTrue($contractSigned->canMoveTo($commoditySoldToCustomer));
    }

    public function test_can_move_from_commodity_sold_to_customer_to_murabha_offer_issued()
    {
        $commoditySoldToCustomer = FinancingOrderStatus::fromValue(FinancingOrderStatus::CommoditySoldToCustomer);
        $waitingClientWakala = FinancingOrderStatus::fromValue(FinancingOrderStatus::MurabhaOfferIssued);
        $this->assertTrue($commoditySoldToCustomer->canMoveTo($waitingClientWakala));
    }

    public function test_can_move_from_murabha_offer_issued_to_murabaha_sale_completed()
    {
        $murabhaOfferIssued = FinancingOrderStatus::fromValue(FinancingOrderStatus::MurabhaOfferIssued);
        $murabahaSaleCompleted = FinancingOrderStatus::fromValue(FinancingOrderStatus::MurabahaSaleCompleted);
        $this->assertTrue($murabhaOfferIssued->canMoveTo($murabahaSaleCompleted));
    }

    public function test_require_action_statuses()
    {
        $this->assertSame(FinancingOrderStatus::$requireActionStatuses, [
            FinancingOrderStatus::PendingApproval,
            FinancingOrderStatus::CommodityPurchased,
            FinancingOrderStatus::MurabahaSaleCompleted,
        ]);
    }

    public function test_allowed_to_update_statuses()
    {
        $this->assertSame(FinancingOrderStatus::$allowedToUpdateStatuses, [
            FinancingOrderStatus::PendingApproval,
            FinancingOrderStatus::Rejected,
        ]);
    }
}
