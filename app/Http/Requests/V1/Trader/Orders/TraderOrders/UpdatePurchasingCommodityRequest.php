<?php

namespace App\Http\Requests\V1\Trader\Orders\TraderOrders;

use App\Enums\FinancingOrderHistory;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchasingCommodityRequest extends FormRequest
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
        $traderOrder = $this->route('trader_order');

        $isPtpDocumentAttached = $traderOrder->checkOrderHistoryAction(FinancingOrderHistory::AttachPtpDocumentToOrder);
        $isHoldingCertAttached = $traderOrder->checkOrderHistoryAction(FinancingOrderHistory::AttachTtiHoldingCertificateDocument);
        $isLenderOwnershipDocumentAttached = $traderOrder->checkOrderHistoryAction(FinancingOrderHistory::CreateTransferOwnershipToLenderDocument);

        return [
            'ptp_document' => [
                $isPtpDocumentAttached ? 'nullable' : 'required',
                'file',
                'mimes:pdf',
            ],
            'original_holding_certificate' => [
                $isHoldingCertAttached ? 'nullable' : 'required',
                'file',
                'mimes:pdf',
            ],
            'financing_institution_certificate' => [
                'exclude_if:auto_generate_financing_institution_certificate,true',
                $isLenderOwnershipDocumentAttached ? 'nullable' : 'required',
                'file',
                'mimes:pdf',
            ],
            'product' => ['required', 'string'],
            'quantity' => ['required', 'numeric'],
            'amount' => ['required', 'numeric'],
            'currency' => ['required', 'string'],
            'warehouse' => ['required', 'string'],
            'owner' => ['required', 'string'],
            'previous_owner' => ['required', 'string'],
            'date_time_of_purchasing_commodity' => ['required', 'string', 'date_format:Y-m-d H:i:s'],
            'warehouse_or_vault_emirates' => ['required', 'string'],
            'warehouse_or_vault_country' => ['required', 'string'],
            'uom' => ['required', 'string'],
            'exchange_rate' => ['required', 'numeric'],
            'auto_generate_financing_institution_certificate' => ['required', 'boolean'],
        ];
    }
}
