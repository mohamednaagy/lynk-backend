<?php

namespace App\Support\FinancingOrders\StepAndHistories;

class StepHistoriesDictionaryNode
{
    public function __construct(public $status, public $histories)
    {
    }
}
