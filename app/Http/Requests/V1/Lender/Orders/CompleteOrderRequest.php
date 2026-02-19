<?php

namespace App\Http\Requests\V1\Lender\Orders;

use App\Enums\Area;
use App\Models\FinancingOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class CompleteOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $financingOrder = FinancingOrder::findOrFail($this->route('order'));

        return $financingOrder->canBeCompleted(Area::Lender);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'payment_proof' => ['sometimes', 'nullable', 'file', 'mimes:pdf,png,jpeg,jpg', 'max:5120'],
        ];
    }

    /**
     * Handle a failed authorization attempt.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedAuthorization()
    {
        throw ValidationException::withMessages([
            'order' => ['Order cannot be completed'],
        ]);
    }
}
