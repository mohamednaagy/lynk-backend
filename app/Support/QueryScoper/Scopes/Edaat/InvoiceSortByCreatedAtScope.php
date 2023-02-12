<?php

namespace App\Support\QueryScoper\Scopes\Edaat;

use App\Support\QueryScoper\QueryScoper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class InvoiceSortByCreatedAtScope extends QueryScoper
{
    /**
     * Prepare data for violation
     *
     * @return array
     */
    public function prepareData(): array
    {
        return [
            'oldest' => (bool) Request::query('oldest'),
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
                'oldest' => ['required', 'boolean'],
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
        if ($data['oldest']) {
            return $builder->oldest();
        }

        return $builder->latest();
    }
}
