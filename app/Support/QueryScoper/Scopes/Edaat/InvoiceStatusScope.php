<?php

namespace App\Support\QueryScoper\Scopes\Edaat;

use App\Enums\EdaatInvoiceStatus;
use App\Support\QueryScoper\QueryScoper;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class InvoiceStatusScope extends QueryScoper
{
    /**
     * Prepare data for validation
     */
    public function prepareData(): array
    {
        return [
            'status' => Request::query('status'),
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
                'status' => ['required', new EnumValue(EdaatInvoiceStatus::class, false)],
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
        return $builder->where('status', $data['status']);
    }
}
