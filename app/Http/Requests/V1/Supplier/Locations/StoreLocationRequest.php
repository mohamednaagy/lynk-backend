<?php

namespace App\Http\Requests\V1\Supplier\Locations;

use App\Models\SupplierLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLocationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
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
            'unique_identifier' => [
                'required',
                'string',
                'min:3',
                'max:16',
                Rule::unique(SupplierLocation::class, 'unique_identifier')->where('company_id', Auth()->user()->company_id),
            ],
            'name' => ['required', 'string',  'max:32'],
            'description' => ['nullable', 'string', 'max:256'],
        ];
    }
}
