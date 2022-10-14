<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Validation\Rule;

class sendResetPasswordLinkRequest extends FormRequest
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
            'email' => ['required', 'email', Rule::exists(User::class, 'email')],
            'unique_name' => ['nullable', 'string', Rule::exists(Company::class, 'unique_name')],
            'redirect_URL' => ['required', 'url']
        ];
    }
}
