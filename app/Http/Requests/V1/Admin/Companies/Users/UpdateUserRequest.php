<?php

namespace App\Http\Requests\V1\Admin\Companies\Users;

use App\Enums\Area;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        return  [
            'first_name' => ['required', 'string', 'min:3', 'max:100'],
            'last_name' => ['required', 'string', 'min:3', 'max:100'],
            'email' => [
                'required',
                'email:filter',
                Rule::unique(User::class, 'email')
                    ->ignore($this->route('user')->id)
                    ->where('company_id', $this->route('user')->company_id),
            ],
            'phone_country_code' => ['required_with:phone_number', 'string', 'size:2'],
            'phone_number' => ['required', 'phone:phone_country_code,mobile', 'string'],
            'role' => [
                'required',
                Rule::in(Area::roles(Area::Lender)),
                Rule::notIn(Role::LenderApiUser),
            ],
        ];
    }
}
