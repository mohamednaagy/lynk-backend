<?php

namespace App\Http\Requests\V1\Admin;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\User;
use App\Rules\HostWhitelistRule;
use App\Rules\UrlProtocolRule;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminRequest extends FormRequest
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
                    ->whereNull('company_id'),
            ],
            'role' => ['required', 'string', Rule::in(Area::roles(Area::SuperAdmin))],
            'permissions' => [
                'exclude_if:role,'.Role::Admin,
                'required',
                'array',
                'min:1',
            ],
            'permissions.*' => [
                'exclude_if:role,'.Role::Admin,
                'required',
                'array',
            ],
            'permissions.*.subject' => [
                'exclude_if:role,'.Role::Admin,
                'required',
                'string',
                new EnumValue(Subject::class),
            ],
            'permissions.*.actions' => [
                'exclude_if:role,'.Role::Admin,
                'required',
                'array',
            ],
            'permissions.*.actions.*' => [
                'exclude_if:role,'.Role::Admin,
                'required',
                'string',
                new EnumValue(Action::class),
            ],
            'redirect_url' => ['bail', 'required', 'url', new UrlProtocolRule(), new HostWhitelistRule()],
        ];
    }
}
