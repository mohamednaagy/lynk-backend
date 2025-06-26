<?php

namespace App\Http\Requests\V1\Supplier\Auth;

use App\Enums\CompanyType;
use App\Http\Middleware\EnsureFrontendRequestsAreStatefulWithoutCookie;
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
        $isRequestFromFromFrontend = EnsureFrontendRequestsAreStatefulWithoutCookie::fromFrontend(request());
        $recaptchaRoles = [];
        if ($isRequestFromFromFrontend) {
            $recaptchaRoles['g-recaptcha-response'] = ['required', 'recaptcha'];
        }

        $validationRules = [
            'unique_name' => ['required', 'string',
                Rule::exists('companies', 'unique_name')->where(function ($query) {
                    $query->where('type', CompanyType::Supplier);
                }),
            ],
            'email' => ['required', 'string', 'email:filter'],
            'password' => ['required', 'string'],
            'source' => ['required', 'string'],

        ];

        return array_merge($recaptchaRoles, $validationRules);
    }
}
