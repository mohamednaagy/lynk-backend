<?php

namespace App\Http\Requests\V1\Admin\Auth;

use App\Enums\Action;
use App\Enums\Subject;
use App\Rules\verifyPermissionStructure;
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
            'email' => ['required', 'email', 'unique:users,email'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'array', new verifyPermissionStructure()],
            'permissions.*.subject' => ['required', 'string', Rule::in(Subject::getValues())],
            'permissions.*.actions' => ['required', 'array'],
            'permissions.*.actions.*' => [Rule::in(Action::getValues())],
            'redirect_url' => ['required'],
        ];
    }
}
