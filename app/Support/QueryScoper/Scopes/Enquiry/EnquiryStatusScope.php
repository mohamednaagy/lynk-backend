<?php

namespace App\Support\QueryScoper\Scopes\Enquiry;

use App\Enums\EnquiryStatus;
use App\Support\QueryScoper\QueryScoper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EnquiryStatusScope extends QueryScoper
{
    /**
     * Prepare data for violation
     *
     * @return array
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
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validator($data): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make(
            $data,
            [
                'status' => ['required', 'int', Rule::in(EnquiryStatus::getValues())],
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
        return $builder->where('status', $data['status']);
    }
}
