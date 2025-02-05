<?php

namespace App\Http\Requests\V1\Admin\Lenders\Orders\TraderOrders;

use App\Models\FinancingOrder;
use Illuminate\Foundation\Http\FormRequest;

class CompleteOrderRequest extends FormRequest
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
        $financingOrder = FinancingOrder::findOrFail($this->route('order'));

        if ($financingOrder->canBeCompleted()) {
            return [
                'payment_proof' => ['sometimes', 'nullable', 'file', 'mimes:pdf,png,jpeg,jpg', 'max:5120'],
            ];
        } else {
            return json_encode(['message' => 'Order cannot be completed']);
        }
    }
}
