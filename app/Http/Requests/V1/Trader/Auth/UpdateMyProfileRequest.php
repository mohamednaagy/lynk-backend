<?php

namespace App\Http\Requests\V1\Trader\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMyProfileRequest extends FormRequest
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
            'email' => [
                'required',
                'email',
                Rule::unique(User::class, 'email')
                    ->ignore($this->user()->getAuthIdentifier(), 'id')
                    ->where(
                        'company_id',
                        $this->user()->company_id
                    ),
            ],
            'phone_number' => ['required', 'phone:phone_country_code,mobile', 'string'],
            'phone_country_code' => ['required_with:phone_number', 'string', 'size:2'],
            'password' => ['nullable', 'string', 'confirmed'],
        ];
    }
}
