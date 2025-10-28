<?php

namespace App\Support\QueryScoper\Scopes\Edaat;

use App\Support\QueryScoper\QueryScoper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class InvoiceDateFromScope extends QueryScoper
{
    public function prepareData(): array
    {
        return [
            'date_from' => Request::query('date_from'),
        ];
    }

    public function validator($data): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make(
            $data,
            [
                'date_from' => ['required', 'date_format:Y-m-d'],
            ]
        );
    }

    public function prepareBuilder($builder, $data): Builder
    {
        return $builder->whereDate('created_at', '>=', $data['date_from']);
    }
}
