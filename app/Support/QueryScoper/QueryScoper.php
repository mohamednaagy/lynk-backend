<?php

namespace App\Support\QueryScoper;

use Illuminate\Validation\ValidationException;

abstract class QueryScoper
{
    /**
     * Apply rules
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply($builder)
    {
        try {
            $validated = $this->validator($this->prepareData())->validate();
        } catch (ValidationException $th) {
            return $builder;
        }

        return $this->prepareBuilder($builder, $validated);
    }

    /**
     * Prepare data for vailation
     *
     * @return array
     */
    abstract protected function prepareData();

    /**
     * Get the validator
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    abstract protected function validator($data);

    /**
     * Prepare builder
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  array  $data
     * @return \Illuminate\Database\Eloquent\Builder
     */
    abstract protected function prepareBuilder($builder, $data);
}
