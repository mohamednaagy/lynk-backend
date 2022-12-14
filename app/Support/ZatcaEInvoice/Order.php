<?php

namespace App\Support\ZatcaEInvoice;

use App\Models\FinancingOrder;

class Order
{
    public function __construct(protected FinancingOrder $order, protected float $vat)
    {
    }

    public function getSubtotal()
    {
        return $this->order->amount->formatByDecimal();
    }

    public function getInvoiceDate()
    {
        return $this->order->created_at;
    }

    /**
     * Get order total amount
     *
     * @return \Cknow\Money\Money
     */
    public function getTotalAmount()
    {
        return $this->order->amount->formatByDecimal();
    }

    /**
     * Get order total amount without VAT
     *
     * @return \Cknow\Money\Money
     */
    public function getTotalWithoutVat()
    {
        return $this->order->amount->subtract($this->order->amount->multiply($this->vat))->formatByDecimal();
    }

    /**
     * Get order total VAT
     *
     * @return \Cknow\Money\Money
     */
    public function getTotalVat()
    {
        return $this->order->amount->multiply($this->vat)->formatByDecimal();
    }
}
