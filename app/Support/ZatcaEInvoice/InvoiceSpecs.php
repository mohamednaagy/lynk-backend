<?php

namespace App\Support\ZatcaEInvoice;

use App\Models\Transaction;
use Carbon\Carbon;
use Spatie\MediaLibrary\HasMedia;

class InvoiceSpecs
{
    public function __construct(
        protected HasMedia $associatedModel,
        protected string $sellerName,
        protected string $taxNumber,
        protected Carbon $date,
        protected $totalAmountWithVat,
        protected $vatAmount,
        protected Order $order,
        protected string $buyerName,
        protected Transaction $transaction
    ) {
    }

    public function getAssociatedModel(): HasMedia
    {
        return $this->associatedModel;
    }

    public function getSeller(): string
    {
        return $this->sellerName;
    }

    public function getTaxNumber(): string
    {
        return $this->taxNumber;
    }

    public function getDate()
    {
        return $this->date->timezone('Asia/Riyadh')->toDateTimeString();
    }

    public function getTotalAmountWithVat()
    {
        return $this->totalAmountWithVat;
    }

    public function getVatAmount()
    {
        return $this->vatAmount;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getBuyer(): string
    {
        return $this->buyerName;
    }

    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }
}
