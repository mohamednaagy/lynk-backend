<?php

namespace App\Http\Requests\V1\Admin\Commodities\CommoditySupplier\Users;

use App\Enums\Area;
use App\Models\User;
use App\Rules\HostWhitelistRule;
use App\Rules\UrlProtocolRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        return [
            'first_name' => ['required', 'string', 'min:3', 'max:100'],
            'last_name' => ['required', 'string', 'min:3', 'max:100'],
            'phone_country_code' => ['required_with:phone_number', 'string', 'size:2'],
            'phone_number' => ['required', 'phone:phone_country_code,mobile', 'string'],
            'email' => [
                'required',
                'email:filter',
                Rule::unique(User::class, 'email'),
            ],
            'redirect_url' => ['bail', 'required', 'url', new UrlProtocolRule(), new HostWhitelistRule()],
            'role' => [
                'required',
                Rule::in(Area::roles(Area::CommoditySupplier)),
            ],
        ];
    }
}
