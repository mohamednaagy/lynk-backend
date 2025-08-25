<?php

namespace App\Support\DocumentEngine\Traits;

use App\Models\Transaction;

trait HasTransaction
{
    public function getTransaction(): Transaction
    {
        return $this->context['transaction'] ?? null;
    }
}
