<?php

namespace App\Support\QueryScoper\Scopes\Edaat;

use App\Models\Company;
use App\Support\QueryScoper\QueryScoper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class InvoiceCompanyScope extends QueryScoper
{
    /**
     * Prepare data for violation
     *
     * @return array
     */
    public function prepareData(): array
    {
        return [
            'company_id' => Request::query('company_id'),
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
                'company_id' => ['required', 'integer', Rule::exists(Company::class, 'id')],
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
        return $builder->where('company_id', $data['company_id']);
    }
}
