<?php

namespace App\Http\Requests\V1\Admin\Companies\LenderClients;

use App\Enums\CompanyLenderClientType;
use Illuminate\Foundation\Http\FormRequest;

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
            'type' => ['nullable', 'string', function ($attribute, $value, $fail) {
                $values = explode(',', $value);
                $allowed = CompanyLenderClientType::getValues();

                foreach ($values as $val) {
                    if (! in_array((int) $val, $allowed, true)) {
                        return $fail($attribute, "The selected {$attribute} is invalid.");
                    }
                }
            }],
            'national_id' => ['nullable', 'integer', 'max_digits:10'],
        ];
    }
}
