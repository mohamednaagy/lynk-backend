<?php

namespace App\Support\QueryScoper\Scopes\Edaat;

use App\Support\QueryScoper\QueryScoper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class InvoiceCompaniesScope extends QueryScoper
{
    public function prepareData(): array
    {
        $companyIds = Request::query('company_id');

        // Handle null or empty values
        if (empty($companyIds)) {
            return [
                'company_id' => [],
            ];
        }

        // Normalize to integers and unique values
        $companyIds = array_values(array_unique(array_map('intval', $companyIds)));

        return [
            'company_id' => $companyIds,
        ];
    }

    public function validator($data): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make(
            $data,
            [
                'company_id' => ['required', 'array', 'min:1'],
                'company_id.*' => ['required', 'integer', 'exists:companies,id'],
            ]
        );
    }

    public function prepareBuilder($builder, $data): Builder
    {
        if (empty($data['company_id'])) {
            return $builder;
        }

        return $builder->whereIn('company_id', $data['company_id']);
    }
}
