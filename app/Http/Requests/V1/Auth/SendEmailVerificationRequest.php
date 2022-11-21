<?php

namespace App\Http\Requests\V1\Auth;

use App\Models\User;
use App\Rules\HostWhitelistRule;
use App\Rules\UrlProtocolRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendEmailVerificationRequest extends FormRequest
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

    public function rules(): array
    {
        return [
            'email' => ['sometimes', 'email', Rule::unique(User::class, 'email')->ignore($this->user()->id)],
            'redirect_url' => ['required', 'url', new UrlProtocolRule(), new HostWhitelistRule],
        ];
    }
}
