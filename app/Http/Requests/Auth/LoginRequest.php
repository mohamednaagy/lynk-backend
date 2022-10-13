<?php

namespace App\Http\Requests\Auth;

use App\Models\Company;
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
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'source' => ['required', 'string'],
        ];
    }
}
