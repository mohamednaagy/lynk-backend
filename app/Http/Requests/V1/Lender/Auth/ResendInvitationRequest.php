<?php

namespace App\Http\Requests\V1\Lender\Auth;

use App\Rules\HostWhitelistRule;
use Illuminate\Foundation\Http\FormRequest;

class ResendInvitationRequest extends FormRequest
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
            'email' => ['required', 'email'],
            'redirect_url' => ['required', 'url', new HostWhitelistRule()],
        ];
    }
}
