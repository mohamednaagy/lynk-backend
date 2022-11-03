<?php

namespace App\Support\QueryScoper\Scopes\Enquiry;

use App\Support\QueryScoper\QueryScoper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class EnquiryCreatorScope extends QueryScoper
{
    /**
     * Prepare data for violation
     *
     * @return array
     */
    public function prepareData(): array
    {
        return [
            'creator' => Request::query('creator'),
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
                'creator' => ['required', 'string'],
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
        $creator = $data['creator'];

        return $builder->where(function ($query) use ($creator) {
            $query->where('name', 'LIKE', '%'.$creator.'%')
                ->orWhere('email', 'LIKE', '%'.$creator.'%')
                ->orWhere('phone_number', 'LIKE', '%'.$creator.'%');
        })
            ->orWhereHas('user', function ($q) use ($creator) {
                $q->where('first_name', 'LIKE', '%'.$creator.'%')
                    ->orWhere('last_name', 'LIKE', '%'.$creator.'%');
            });
    }
}
