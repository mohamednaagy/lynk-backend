<?php

namespace App\Http\Requests\V1\Admin\Enquiries;

use App\Enums\EnquiryStatus;
use App\Rules\HostWhitelistRule;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

class ReplyToEnquiryRequest extends FormRequest
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
            'body' => ['required', 'string', 'max:1000'],
            'status' => ['nullable', 'int', new EnumValue(EnquiryStatus::class)],
            'redirect_url' => ['required', 'url', new HostWhitelistRule()],
        ];
    }
}
