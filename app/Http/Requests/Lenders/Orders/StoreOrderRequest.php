<?php
namespace App\Http\Requests\Lenders\Orders;

use App\Rules\ValidateSAID;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
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
    public function rules()
    {
        $tenant = tenant();

        return [
            'reference_number' => ['nullable', $tenant->unique('financing_orders', 'refrence_number')],
            'national_id' => ['required', 'digits:10', new ValidateSAID],
            'amount' => ['required', 'numeric'],
            'selling_price' => ['required', 'numeric'],
            'contract' => ['file', 'required'],
            'power_of_attorney' => ['file', 'required'],
        ];
    }
}
