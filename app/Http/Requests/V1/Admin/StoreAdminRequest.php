<?php

namespace App\Http\Requests\V1\Admin;

use App\Enums\Action;
use App\Enums\Subject;
use App\Rules\HostWhitelistRule;
use App\Rules\UrlProtocolRule;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

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
            'email' => ['required', 'email', 'unique:users,email'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'array'],
            'permissions.*.subject' => ['required', 'string', new EnumValue(Subject::class)],
            'permissions.*.actions' => ['required', 'array'],
            'permissions.*.actions.*' => ['required', 'string', new EnumValue(Action::class)],
            'redirect_url' => ['required', 'url', new UrlProtocolRule(), new HostWhitelistRule()],
        ];
    }
}
