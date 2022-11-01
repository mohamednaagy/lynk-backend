<?php

namespace App\Http\Requests\V1\Admin;

use App\Enums\Action;
use App\Enums\Subject;
use App\Rules\HostWhitelistRule;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;
<<<<<<< app/Http/Requests/V1/Admin/StoreAdminRequest.php
use Illuminate\Validation\Rules\Password;
use function trans;

class StoreAdminRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'min:3', 'max:100'],
            'last_name' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'array'],
            'permissions.*.subject' => ['required', 'string', new EnumValue(Subject::class)],
            'permissions.*.actions' => ['required', 'array'],
            'permissions.*.actions.*' => ['required', 'string', new EnumValue(Action::class)],
            'redirect_url' => ['required', 'url', new HostWhitelistRule()],
        ];

        if (! empty($this->role)) {
            $rules['role'] = ['required', 'string', 'exists:roles,name'];
        }

        if (! empty($this->permissions)) {
            $rules['permissions.*'] = ['required', 'array', 'distinct'];
        }

        return $rules;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'phone_number.phone' => trans('validation.phone'),

        ];
    }
}
