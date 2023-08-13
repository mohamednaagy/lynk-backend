<?php

namespace App\Support\QueryScoper\Scopes\Company;

use App\Support\QueryScoper\QueryScoper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class CompanySearchScope extends QueryScoper
{
    /**
     * Prepare data for vailation
     *
     * @return array
     */
    public function prepareData()
    {
        return [
            'search' => Request::query('search'),
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
                'search' => ['required', 'string'],
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
        $search = $data['search'];

        return $builder->where(function (Builder $builder) use ($search) {
            $builder->where('id', $search)
                ->orWhere('name', 'LIKE', "%$search%")
                ->orWhere('company_cr', 'LIKE', "%$search%")
                ->orWhere('unique_name', 'LIKE', "%$search%");
        });
    }
}
