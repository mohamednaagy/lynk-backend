<?php

namespace App\Http\Requests\V1\Supplier\Inventories;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property string $area
 */
class UpdateLocalMarketInventoryRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'total_units' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'total_units.min' => 'This field requires a positive integer value greater than 0',
        ];
    }
}
