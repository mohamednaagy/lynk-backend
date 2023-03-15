<?php

namespace App\Support\FinancingOrders\StepAndHistories;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;

class StepHistoriesDictionary
{
    public \SplDoublyLinkedList $dictionaryNodeList;

    public const StepToHistoriesDictionary = [
        FinancingOrderStatus::PendingApproval => [],
        FinancingOrderStatus::Approved => [],
        FinancingOrderStatus::WaitingPurchasingCommodity => [
            FinancingOrderHistory::GetTtiId,
        ],
        FinancingOrderStatus::RespondedToPtp => [
            FinancingOrderHistory::RespondPtp,
        ],
        FinancingOrderStatus::PtpDocumentRetrieved => [
            FinancingOrderHistory::GetPtpDocument,
            FinancingOrderHistory::AttachPtpDocumentToOrder,
            FinancingOrderHistory::GetTtiHoldingCertificateDocument,
            FinancingOrderHistory::AttachTtiHoldingCertificateDocument,
        ],
        FinancingOrderStatus::CommodityPurchased => [
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
        ],
        FinancingOrderStatus::ContractSigned => [
            FinancingOrderHistory::ContractSigned,
        ],
        FinancingOrderStatus::CommoditySoldToCustomer => [
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument,
        ],
        FinancingOrderStatus::WaitingClientWakala => [],
        FinancingOrderStatus::ClientWakalaCompleted => [
            FinancingOrderHistory::ClientWakalaAccepted,
        ],
        FinancingOrderStatus::MurabhaOfferIssued => [
            FinancingOrderHistory::IssueMurabahaOffer,
            FinancingOrderHistory::GetMurabahaPurchaseOfferDocument,
            FinancingOrderHistory::AttachMpoDocument,
        ],
        FinancingOrderStatus::MurabahaSaleCompleted => [
            FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument,
            FinancingOrderHistory::AttachWarrantAmendmentExceptWarrantNoDocument,
            FinancingOrderHistory::MurabahaSaleCompleted,
        ],
    ];

    public function __construct()
    {
        $this->dictionaryNodeList = new \SplDoublyLinkedList();

        foreach (self::StepToHistoriesDictionary as $status => $histories) {
            $this->dictionaryNodeList->push(new StepHistoriesDictionaryNode($status, $histories));
        }
    }

    public function getPreviousStepOf($status)
    {
        $this->dictionaryNodeList->rewind();
        while ($this->dictionaryNodeList->valid()) {
            if ($this->dictionaryNodeList->current()->status == $status) {
                $this->dictionaryNodeList->prev();

                return $this->dictionaryNodeList->current();
            }
            $this->dictionaryNodeList->next();
        }
    }

    public function getNextStepOf($status)
    {
        $this->dictionaryNodeList->rewind();
        while ($this->dictionaryNodeList->valid()) {
            if ($this->dictionaryNodeList->current()->status == $status) {
                $this->dictionaryNodeList->next();

                return $this->dictionaryNodeList->current();
            }
            $this->dictionaryNodeList->next();
        }
    }

    public function getStepOf($status)
    {
        $this->dictionaryNodeList->rewind();
        while ($this->dictionaryNodeList->valid()) {
            if ($this->dictionaryNodeList->current()->status == $status) {
                return $this->dictionaryNodeList->current();
            }
            $this->dictionaryNodeList->next();
        }
    }

    public function getStepByHistory($history)
    {
        $this->dictionaryNodeList->rewind();
        while ($this->dictionaryNodeList->valid()) {
            if (in_array($history, $this->dictionaryNodeList->current()->histories)) {
                return $this->dictionaryNodeList->current();
            }

            $this->dictionaryNodeList->next();
        }
    }
}
