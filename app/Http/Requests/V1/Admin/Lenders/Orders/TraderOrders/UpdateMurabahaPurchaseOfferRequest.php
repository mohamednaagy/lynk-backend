<?php

namespace App\Http\Requests\V1\Admin\Lenders\Orders\TraderOrders;

use App\Enums\FinancingOrderHistory;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMurabahaPurchaseOfferRequest extends FormRequest
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
        $traderOrder = $this->route('trader_order');

        $isMpoDocumentAttached = $traderOrder->checkOrderHistoryAction(FinancingOrderHistory::AttachMpoDocument);

        return [
            'document' => [
                $isMpoDocumentAttached ? 'nullable' : 'required',
                'file',
                'mimes:pdf',
            ],
        ];
    }
}
