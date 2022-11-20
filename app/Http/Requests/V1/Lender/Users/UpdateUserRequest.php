<?php

namespace App\Http\Requests\V1\Lender\Users;

use App\Enums\Area;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

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
        // __REVIEW__ add attributes translations
        // See: https://laravel.com/docs/9.x/validation#specifying-attribute-in-language-files
        return  [
            'first_name' => ['required', 'string', 'min:3', 'max:100'],
            'last_name' => ['required', 'string', 'min:3', 'max:100'],
            'email' => [
                'required', 'email',
                // __REVIEW__ replace with tenant()->unique(User::class, 'email')
                Rule::unique(User::class, 'email')
                    ->ignore($this->route('user')->id)
                    ->where('company_id', tenant('id')),
            ],
            'phone_country_code' => ['required_with:phone_number', 'string', 'size:2'],
            // __REVIEW__ add "mobile" type to phone validation rule so that it accepts mobile numbers (not landline numbers)
            // See Laravel Phone docs
            'phone_number' => ['required', 'phone:phone_country_code', 'string'],
            'role' => [
                'required',
                Rule::in(
                    Arr::except(Area::getRolesPerAreaMap()[Area::Lender], [Role::LenderApiUser])
                ),
            ],
        ];
    }
}
