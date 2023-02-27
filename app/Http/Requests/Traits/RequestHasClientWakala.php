<?php

namespace App\Http\Requests\Traits;

use App\Enums\FinancingOrderProceedCase;
use Illuminate\Validation\Validator;

trait RequestHasClientWakala
{
    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator)
    {
        $validator->after(
            function ($validator) {
                if ($this->isClientWakalaNotProvidedIfNeeded()) {
                    $validator->errors()->add(
                        'client_wakala',
                        __('validation.required', ['attribute' => __('validation.attributes.client_wakala')])
                    );
                }
            }
        );
    }

    private function isClientWakalaNotProvidedIfNeeded()
    {
        return  $this->validated('case') == FinancingOrderProceedCase::ClientWakalaAccepted
            && $this->route('order')?->is_verification_required === false
            && is_null($this->validated('client_wakala'));
    }
}
