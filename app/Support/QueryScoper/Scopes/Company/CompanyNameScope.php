<?php

namespace App\Support\QueryScoper\Scopes\Company;

use App\Support\QueryScoper\QueryScoper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class CompanyNameScope extends QueryScoper
{
    public function prepareData(): array
    {
        return [
            'name' => Request::query('name'),
        ];
    }

    public function validator($data): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:100'],
        ]);
    }

    public function prepareBuilder($builder, $data): Builder
    {
        return $builder->where('name', 'LIKE', '%'.$data['name'].'%');
    }
}
