<?php

namespace App\Support\QueryScoper\Scopes\Lender\Edaat;

use App\Support\QueryScoper\QueryScoper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class InvoiceNumberScope extends QueryScoper
{
    /**
     * Prepare data for violation
     *
     * @return array
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
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validator($data): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make(
            $data,
            [
                'invoice_number' => ['nullable', 'string', 'max:255'],
            ]
        );
    }

    /**
     * Prepare builder
     *
     * @param  Builder  $builder
     * @param  array  $data
     * @return Builder
     */
    public function prepareBuilder($builder, $data): Builder
    {
        return ! empty($data['invoice_number']) ? $builder->where(function ($query) use ($data) {
            $query->orWhere('invoice_number', $data['invoice_number']);
        }) : $builder;
    }
}
