<?php

namespace App\Http\Requests\V1\Auth;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'unique_name' => ['nullable', 'string',  Rule::exists(Company::class, 'unique_name')],
            'email' => ['required', 'string', 'email', 'exists:users,email'],
            'password' => ['required', 'string'],
            'source' => ['required', 'string'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(
            function ($validator) {
                $this->makeUniqueNameRequiredWhenEmailDuplicated($validator);
            }
        );
    }

    public function makeUniqueNameRequiredWhenEmailDuplicated($validator)
    {
        if (User::whereEmail($this->validated('email'))->count() > 1 && is_null($this->validated('unique_name'))) {
            $validator->errors()->add('unique_name', __('validation.required', ['attribute' => 'unique_name']));
        }
    }
}
