<?php

namespace App\Http\Requests\V1\Admin\Lenders\Orders\TraderOrders;

use App\Enums\Trader;
use App\Models\TraderOrder;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMurabhaCompleteDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
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
        $traderOrder = TraderOrder::find($this->route('trader_order'));

        $documentKey = $traderOrder->provider == Trader::Lynk ? 'sell_confirmation_document' : 'document';

       return  [
        $documentKey => ['required', 'file', 'mimes:pdf'],
       ];
    }

    public function messages(): array
    {
        return [
            'sell_confirmation_document.required' => 'This field is required.',
            'sell_confirmation_document.mimes' => 'The Sell Confirmation Certificate must be a PDF file.',
        ];
    }
}
