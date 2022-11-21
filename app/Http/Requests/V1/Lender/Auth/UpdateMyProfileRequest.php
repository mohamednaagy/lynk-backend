<?php

namespace App\Http\Requests\V1\Lender\Auth;

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
            'phone_number' => ['required', 'phone:phone_country_code,mobile', 'string'],
            'phone_country_code' => ['required', 'string', 'size:2'],
            'password' => ['nullable', 'string', 'min:8', 'max:100'],
            'email' => [
                'required',
                'email',
                Rule::unique(User::class, 'email')
                    ->ignore(
                        $this->user()->id
                    )
                    ->where(
                        'company_id', $this->user()->company_id
                    ),
            ],

        ];
    }
}
