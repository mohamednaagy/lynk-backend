<?php

namespace App\Support\QueryScoper\Scopes\Lender\Edaat;

use App\Support\QueryScoper\QueryScoper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;

class CompanyInvoiceScope extends QueryScoper
{
    /**
     * Prepare data for violation
     *
     * @return array
     */
    public function prepareData(): array
    {
        return [];
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
        return $builder->where('company_id', tenant()->id);
    }
}
