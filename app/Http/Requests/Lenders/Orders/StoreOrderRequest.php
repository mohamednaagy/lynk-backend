<?php
namespace App\Http\Requests\Lenders\Orders;

use App\Rules\ValidateSAID;
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
        return [
            'reference_number' => ['nullable', 'unique:financing_orders,reference_number'],
            'national_id' => ['required', 'digits:10', new ValidateSAID],
            'amount' => ['required', 'numeric'],
            'selling_price' => ['required', 'numeric'],
            'contract' => ['file', 'required'],
            'power_of_attorney' => ['file', 'required'],
        ];
    }

    public function validated($key = null, $default = null)
    {
        return array_merge(parent::validated($key = null, $default = null), [
            'company_id' => (int) $this->header('x-tenant')
        ]);
    }
}
