<?php

namespace App\Http\Requests\V1\Enquiries;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnquiryRequest extends FormRequest
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
        $rules = [
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:1000'],
        ];

        if (isset($this->email)) {
            $rules = array_merge($rules, [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'max:255'],
                'phone_country_code' => ['required_with:phone_number', 'string', 'size:2'],
                'phone_number' => ['required', 'phone:phone_country_code', 'string'],
            ]);
        }

        return $rules;
    }
}
