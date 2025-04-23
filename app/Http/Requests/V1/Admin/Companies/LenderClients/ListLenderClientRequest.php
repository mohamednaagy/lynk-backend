<?php

namespace App\Http\Requests\V1\Admin\Companies\LenderClients;

use App\Enums\CompanyLenderClientType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListLenderClientRequest extends FormRequest
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
            'name' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(CompanyLenderClientType::getValues())],
            'national_id' => ['nullable', 'integer', 'max_digits:10'],
        ];
    }
}
