<?php

namespace App\Support\Transactions\Descriptions\Generators;

use App\Support\Transactions\Descriptions\GeneratorBase;
use Bavix\Wallet\Models\Transaction;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Arr;

class TopUpType extends GeneratorBase
{
    protected function generateMessage(Transaction $transaction, $locale): array|string|Translator|Application|null
    {
        $description = Arr::get($transaction->meta, 'description');

        return __("transaction-description.$description", [], $locale);
    }
}
