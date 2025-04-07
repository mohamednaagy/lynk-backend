<?php

namespace App\Support\QueryScoper\Scopes\Enquiry;

use App\Enums\EnquiryStatus;
use App\Support\QueryScoper\QueryScoper;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class EnquiryStatusScope extends QueryScoper
{
    /**
     * Prepare data for violation
     */
    public function prepareData(): array
    {
        return [
            'status' => (int) Request::query('status'),
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
                'status' => ['required', 'int', new EnumValue(EnquiryStatus::class)],
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
