<?php

namespace App\Http\Requests\V1\Lender\Users;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
        return  [
            'first_name' => ['required', 'min:3', 'string', 'max:100'],
            'last_name' => ['required', 'min:3', 'string', 'max:100'],
            'email' => ['required', 'email'],
        ];
    }
}
