<?php

namespace App\Http\Requests\V1\Auth;

use App\Enums\Role;
use App\Http\Middleware\EnsureFrontendRequestsAreStatefulWithoutCookie;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoginRequest extends FormRequest
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
        // check it if is get from api integration or from api of system
        $isItNeedRecaptcha = EnsureFrontendRequestsAreStatefulWithoutCookie::fromFrontend(request());

        //check User doesnt has ApiAdmin Role
        $user = User::where('email', $this->email)->first();
        $recaptchaRoles = [];
        if ($isItNeedRecaptcha && ! $user->hasRole('ApiAdmin')) {
            $recaptchaRoles['g-recaptcha-response'] = 'required|recaptcha';
        }

        $validationRules = [
            'unique_name' => ['nullable', 'string', Rule::exists(Company::class, 'unique_name')],
            'email' => ['required', 'string', 'email:filter'],
            'password' => ['required', 'string'],
            'source' => ['required', 'string'],

        ];

        return array_merge($recaptchaRoles, $validationRules);
    }
}
