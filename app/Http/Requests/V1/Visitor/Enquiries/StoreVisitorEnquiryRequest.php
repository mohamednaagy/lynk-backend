<?php

namespace App\Http\Requests\V1\Visitor\Enquiries;

use App\Rules\HostWhitelistRule;
use App\Rules\UrlProtocolRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreVisitorEnquiryRequest extends FormRequest
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:1000'],
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone_country_code' => ['required_with:phone_number', 'string', 'size:2'],
            'phone_number' => ['required', 'phone:phone_country_code,mobile', 'string'],
            'redirect_url' => ['bail', 'required', 'url', new UrlProtocolRule(), new HostWhitelistRule()],
        ];
    }
}
