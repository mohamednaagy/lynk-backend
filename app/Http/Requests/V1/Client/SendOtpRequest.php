<?php

namespace App\Http\Requests\V1\Client;

use App\Rules\ValidateSAID;
use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
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
            'national_id' => ['required', 'string', new ValidateSAID],
            'order_id' => ['required', 'integer', 'gt:0'],
        ];
    }
}
