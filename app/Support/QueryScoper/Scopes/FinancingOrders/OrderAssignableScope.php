<?php

namespace App\Support\QueryScoper\Scopes\FinancingOrders;

use App\Support\QueryScoper\QueryScoper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class OrderAssignableScope extends QueryScoper
{
    private const NOT_ASSIGNED_FILTER_VALUE = '-1';

    /**
     * Prepare builder
     *
     * @param  Builder  $builder
     * @param  array  $data
     */
    public function prepareBuilder($builder, $data): Builder
    {
        if (empty($data['assignable_id'])) {
            return $builder;
        }

        if (in_array(self::NOT_ASSIGNED_FILTER_VALUE, $data['assignable_id'], true)) {
            return $builder->where(function ($query) use ($data) {
                $query->whereIn('assignable_id', $data['assignable_id'])
                    ->orWhereNull('assignable_id');
            });
        }

        return $builder->whereIn('assignable_id', $data['assignable_id']);
    }

    /**
     * Prepare data
     */
    public function prepareData(): array
    {
        $assignableId = Request::query('assignable_id');

        // Handle both array input and comma-separated string input
        $assignableIdArray = [];

        if (is_string($assignableId) && ! empty($assignableId)) {
            $assignableIdArray = array_map('trim', explode(',', $assignableId));
        } elseif (is_array($assignableId)) {
            $assignableIdArray = $assignableId;
        }

        return [
            'assignable_id' => $assignableIdArray,
        ];
    }

    /**
     * Get the validator
     *
     * @param  array  $data
     */
    public function validator($data): \Illuminate\Contracts\Validation\Validator
    {
        $validUserIds = $this->getValidUserIds();

        return Validator::make($data, [
            'assignable_id' => ['nullable', 'array'],
            'assignable_id.*' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($validUserIds) {
                    if ($value != self::NOT_ASSIGNED_FILTER_VALUE && ! in_array($value, $validUserIds)) {
                        $fail('The '.$attribute.' is invalid.');
                    }
                },
            ],
        ]);
    }

    /**
     * Get valid user IDs for validation
     */
    protected function getValidUserIds(): array
    {
        return \App\Models\User::pluck('id')->toArray();
    }
}
