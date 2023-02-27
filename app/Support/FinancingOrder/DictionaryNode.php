<?php

namespace App\Support\FinancingOrder;

class DictionaryNode
{
    public function __construct(public $status, public $histories)
    {
    }
}
