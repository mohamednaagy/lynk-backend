<?php

namespace App\Http\Requests\V1\Supplier\Inventories;

use App\Models\SupplierLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property string $area
 */
class UpdateInventoryRequest extends FormRequest
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
            'unique_identifier' => [
                'required',
                'string',
                'min:3',
                'max:16',
                Rule::unique(SupplierLocation::class, 'unique_identifier')->ignore($this->route('location')),
            ],
            'name' => ['required', 'string',  'max:32'],
            'description' => ['nullable', 'string', 'max:256'],
        ];
    }
}
