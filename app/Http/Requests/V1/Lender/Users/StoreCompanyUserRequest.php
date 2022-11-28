<?php

namespace App\Http\Requests\V1\Lender\Users;

use App\Enums\Area;
use App\Enums\Role;
use App\Models\User;
use App\Rules\HostWhitelistRule;
use App\Rules\UrlProtocolRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class StoreCompanyUserRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // __REVIEW__ translate attributes if needed
        return [
            'first_name' => ['required', 'string', 'min:3', 'max:100'],
            'last_name' => ['required', 'string', 'min:3', 'max:100'],
            'phone_country_code' => ['required_with:phone_number', 'string', 'size:2'],
            'phone_number' => ['required', 'phone:phone_country_code,mobile', 'string'],
            'email' => [
                'required',
                'email',
                Rule::unique(User::class, 'email')
                    ->where('company_id', tenant('id')),
            ],
            'redirect_url' => ['bail', 'required', 'url', new UrlProtocolRule(), new HostWhitelistRule()],
            'role' => [
                'required',
                Arr::except((array) Rule::in(Area::roles(Area::Lender)), [Role::LenderApiUser]),
            ],
        ];
    }
}
