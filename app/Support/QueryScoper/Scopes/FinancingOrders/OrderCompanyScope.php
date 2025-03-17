<?php

namespace App\Support\QueryScoper\Scopes\FinancingOrders;

use App\Support\QueryScoper\QueryScoper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class OrderCompanyScope extends QueryScoper
{
    /**
     * Prepare data for validation
     *
     * @return array
     */
    public function prepareData()
    {
        $company = Request::query('company');

        // Handle both array input and comma-separated string input
        $companyArray = [];

        if (is_string($company) && ! empty($company)) {
            $companyArray = array_map('trim', explode(',', $company));
        } elseif (is_array($company)) {
            $companyArray = $company;
        }

        return [
            'company' => $companyArray,
        ];
    }

    /**
     * Get the validator
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validator($data)
    {
        return Validator::make(
            $data,
            [
                'company' => ['required', 'array', 'min:1'],
                'company.*' => ['required', 'integer', 'exists:companies,id'],
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
    public function prepareBuilder($builder, $data)
    {
        if (empty($data['company'])) {
            return $builder;
        }

        return $builder->whereIn('company_id', $data['company']);
    }
}
