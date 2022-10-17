<?php

namespace App\Http\Requests\V1\Lender\Users;

use App\Rules\DomainWhiteListRule;
use App\Rules\WhiteListRule;
use Illuminate\Foundation\Http\FormRequest;


class StoreUserRequest extends FormRequest
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
    public function rules(): array
    {
        return  [
            'first_name' => ['required', 'min:3', 'string', 'max:100'],
            'last_name' => ['required', 'min:3', 'string', 'max:100'],
            'phone_country_code' => ['required_with:phone_number', 'string', 'size:2'],
            'phone_number' => ['required', 'phone:phone_country_code', 'string'],
            'email' => ['required', 'email'],
            'redirect_url' => ['required', 'url', new DomainWhiteListRule()]
        ];
    }
}
