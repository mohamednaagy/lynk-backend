<?php

namespace App\Http\Requests\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

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
            'email' => ['sometimes', 'email', 'unique:users,email,'.auth()->user()->id],
            'redirect_url' => ['required', 'url'],
        ];
    }
}
