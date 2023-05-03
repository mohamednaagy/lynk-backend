<?php

namespace App\Http\Requests\V1\Admin\Auth;

use Illuminate\Foundation\Http\FormRequest;

class CompleteAdminRegisterRequest extends FormRequest
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
            'password' => ['required', 'string', 'confirmed'],
            'source' => ['required', 'string', 'max:100'],
        ];
    }
}
