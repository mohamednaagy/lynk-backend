<?php

namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use function trans;

class UpdateAdminRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = [
            'first_name' => ['required', 'string', 'min:3', 'max:100'],
            'last_name' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->id)],
            'password' => ['required', 'string', 'confirmed'],
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
