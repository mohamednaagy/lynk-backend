<?php

namespace App\Http\Requests\Auth;

use App\Http\Middleware\EnsureFrontendRequestsAreStatefulWithoutCookie;
use App\Models\User;
use App\Rules\HostWhitelistRule;
use App\Rules\UrlProtocolRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendLinkRequest extends FormRequest
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

        $recaptchaRoles = [];
        if ($isItNeedRecaptcha) {
            $recaptchaRoles['g-recaptcha-response'] = 'required|recaptcha';
        }

        $validationRules = [
            'email' => ['required', 'email:filter', Rule::exists(User::class, 'email')],
            'company_unique_name' => ['nullable', 'string'],
            'redirect_url' => ['bail', 'required', 'url', new UrlProtocolRule(), new HostWhitelistRule()],
        ];

        return array_merge($recaptchaRoles, $validationRules);

    }
}
