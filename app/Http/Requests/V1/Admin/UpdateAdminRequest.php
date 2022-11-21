<?php

namespace App\Http\Requests\V1\Admin;

use App\Enums\Action;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\User;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminRequest extends FormRequest
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
     * @return array
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'min:3', 'max:100'],
            'last_name' => ['required', 'string', 'min:3', 'max:100'],
            'email' => [
                'required',
                'email',
                Rule::unique(User::class, 'email')
                    ->whereNull('company_id')
                    ->ignore($this->admin->id),
            ],
            'password' => ['nullable', 'string', 'confirmed'],
            // __REVIEW__ instead of new EnumValue(Role::class), we should define the allowed roles using Rule::in(...)
            'role' => ['required', 'string', new EnumValue(Role::class)],
            'permissions' => ['required', 'array'],
            'permissions.*' => ['required', 'array'],
            'permissions.*.subject' => ['required', 'string', new EnumValue(Subject::class)],
            'permissions.*.actions' => ['required', 'array'],
            'permissions.*.actions.*' => ['required', 'string', new EnumValue(Action::class)],
        ];
    }
}
