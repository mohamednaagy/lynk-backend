<?php

namespace App\Http\Requests\Auth;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
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
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'company_unique_name' => ['nullable', 'string', Rule::exists(Company::class, 'unique_name')],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
