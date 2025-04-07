<?php

namespace App\Http\Requests\V1\Admin\Companies\LenderClients;

use App\Enums\CompanyLenderClientType;
use App\Models\CompanyLenderClient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLenderClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
            'name' => ['required', 'string', 'max:100'],
            'national_id' => ['required', 'integer', 'max_digits:10',
                Rule::unique(CompanyLenderClient::class, 'national_id')->where('company_id', $this->lender->id)],
            'type' => ['required', Rule::in(CompanyLenderClientType::getValues())],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => __('validation.field_is_required'),
            'national_id.required' => __('validation.field_is_required'),
            'type.required' => __('validation.field_is_required'),
            'name.max' => __('validation.max_string_chars'),
            'national_id.unique' => __('validation.unique_input'),
        ];
    }
}
