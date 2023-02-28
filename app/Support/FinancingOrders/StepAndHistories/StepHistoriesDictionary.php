<?php

namespace App\Support\FinancingOrders\StepAndHistories;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;

class StepHistoriesDictionary
{
    public \SplDoublyLinkedList $DictionaryNodeList;

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
        $this->DictionaryNodeList = new \SplDoublyLinkedList();

        foreach (self::StepToHistoriesDictionary as $status => $histories) {
            $this->DictionaryNodeList->push(new StepHistoriesDictionaryNode($status, $histories));
        }
    }

    public function getPreviousStepOf($status)
    {
        $this->DictionaryNodeList->rewind();
        while ($this->DictionaryNodeList->valid()) {
            if ($this->DictionaryNodeList->current()->status == $status) {
                $this->DictionaryNodeList->prev();

                return $this->DictionaryNodeList->current();
            }
            $this->DictionaryNodeList->next();
        }
    }

    public function getNextStepOf($status)
    {
        $this->DictionaryNodeList->rewind();
        while ($this->DictionaryNodeList->valid()) {
            if ($this->DictionaryNodeList->current()->status == $status) {
                $this->DictionaryNodeList->next();

                return $this->DictionaryNodeList->current();
            }
            $this->DictionaryNodeList->next();
        }
    }

    public function getStepOf($status)
    {
        $this->DictionaryNodeList->rewind();
        while ($this->DictionaryNodeList->valid()) {
            if ($this->DictionaryNodeList->current()->status == $status) {
                return $this->DictionaryNodeList->current();
            }
            $this->DictionaryNodeList->next();
        }
    }
}
