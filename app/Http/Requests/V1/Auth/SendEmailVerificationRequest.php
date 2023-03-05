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
        $emailUniqueRule = Rule::unique(User::class, 'email')
            ->ignore($this->user()->id);

        if ($this->user()->company_id !== null) {
            $emailUniqueRule->where('company_id', $this->user()->company_id);
        } else {
            $emailUniqueRule->whereNull('company_id');
        }

        return [
            'email' => ['sometimes', 'email', $emailUniqueRule],
            'redirect_url' => ['bail', 'required', 'url', new UrlProtocolRule(), new HostWhitelistRule()],
        ];
    }
}
