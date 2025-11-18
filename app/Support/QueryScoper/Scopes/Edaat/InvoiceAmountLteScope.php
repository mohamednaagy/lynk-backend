<?php

namespace App\Support\QueryScoper\Scopes\Edaat;

use App\Support\QueryScoper\QueryScoper;
use Cknow\Money\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class InvoiceAmountLteScope extends QueryScoper
{
    public function prepareData(): array
    {
        return [
            'amount_lte' => Request::query('amount_lte'),
        ];
    }

    public function validator($data): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make(
            $data,
            [
                'amount_lte' => ['required', 'numeric'],
            ]
        );
    }

    public function prepareBuilder($builder, $data): Builder
    {
        $currency = config('app.currency');
        $minor = Money::parseByDecimal($data['amount_lte'], $currency)->getAmount();

        return $builder->where('amount', '<=', $minor);
    }
}
