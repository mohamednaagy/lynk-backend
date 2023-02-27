<?php

namespace App\Support\FinancingOrder;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;

class FinancingOrderDictionary
{
    public $list;

    public function __construct()
    {
        $this->list = new \SplDoublyLinkedList();

        foreach (self::StepToHistoriesDictionary as $status => $histories) {
            $this->list->push(new DictionaryNode($status, $histories));
        }
    }

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

    public function getPreviousStepOf($status)
    {
        $this->list->rewind();
        while ($this->list->valid()) {
            if ($this->list->current()->status == $status) {
                $this->list->prev();

                return $this->list->current();
            }
            $this->list->next();
        }
    }

    public function getNextStepOf($status)
    {
        $this->list->rewind();
        while ($this->list->valid()) {
            if ($this->list->current()->status == $status) {
                $this->list->next();

                return $this->list->current();
            }
            $this->list->next();
        }
    }
}
