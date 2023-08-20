<?php

namespace App\Support\ZatcaEInvoice;

use App\Models\Transaction;
use Spatie\MediaLibrary\HasMedia;

class InvoiceSpecs
{
    public function __construct(
        protected string $key,
        protected HasMedia $associatedModel,
        protected string $seller,
        protected string $taxNumber,
        protected $date,
        protected $totalAmount,
        protected $taxAmount,
        protected Order $order,
        protected string $buyer,
        protected Transaction $transaction
    ) {
    }

    public function getAssociatedModel(): HasMedia
    {
        return $this->associatedModel;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getSeller(): string
    {
        return $this->seller;
    }

    public function getTaxNumber(): string
    {
        return $this->taxNumber;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    public function getTaxAmount()
    {
        return $this->taxAmount;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getBuyer(): string
    {
        return $this->buyer;
    }

    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }
}
