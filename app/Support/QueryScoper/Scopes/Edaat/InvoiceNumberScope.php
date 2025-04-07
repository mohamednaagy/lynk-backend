<?php

namespace App\Support\QueryScoper\Scopes\Edaat;

use App\Support\QueryScoper\QueryScoper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class InvoiceNumberScope extends QueryScoper
{
    /**
     * Prepare data for violation
     */
    public function prepareData(): array
    {
        return [
            'invoice_number' => Request::query('invoice_number'),
        ];
    }

    /**
     * Get the validator
     *
     * @param  array  $data
     */
    public function validator($data): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make(
            $data,
            [
                'invoice_number' => ['required', 'string', 'max:255'],
            ]
        );
    }

    /**
     * Prepare builder
     *
     * @param  Builder  $builder
     * @param  array  $data
     */
    public function prepareBuilder($builder, $data): Builder
    {
        return $builder->where('invoice_number', $data['invoice_number']);
    }
}
