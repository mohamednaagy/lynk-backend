<?php

namespace App\Http\Requests\V1\Trader\Orders\TraderOrders;

use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\Trader;
use App\Models\TraderOrder;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchasingCommodityRequest extends FormRequest
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
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $traderOrder = TraderOrder::query()->findOrFail($this->route('trader_order'));

        return $this->getRules($traderOrder);
    }

    private function getRules(TraderOrder $traderOrder): array
    {
        $isLenderOwnershipDocumentAttached = $traderOrder->hasMedia(TraderOrderMediaCollection::TransferOwnershipToLender);

        $rules = [
            'auto_generate_financing_institution_certificate' => ['required', 'boolean'],
            'financing_institution_certificate' => [
                'exclude_if:auto_generate_financing_institution_certificate,true',
                $isLenderOwnershipDocumentAttached ? 'nullable' : 'required',
                'file',
                'mimes:pdf',
            ],
        ];

        if ($traderOrder->provider == Trader::Lynk) {
            $additionalRules = [
                'products' => ['required', 'array'],
                'products.*.product' => ['required', 'string'],
                'products.*.type' => ['required', 'string'],
                'products.*.quantity' => ['required', 'numeric'],
                'products.*.uom' => ['required', 'string'],
                'products.*.amount' => ['required', 'numeric'],
                'products.*.location' => ['required', 'string'],
                'products.*.currency' => ['required', 'string'],
                'products.*.original_supplier' => ['required', 'string'],
                'products.*.previous_owner' => ['required', 'string'],
            ];
        } else {
            $isPtpDocumentAttached = $traderOrder->hasMedia(TraderOrderMediaCollection::PromiseToPurchase);
            $isHoldingCertAttached = $traderOrder->hasMedia(TraderOrderMediaCollection::TtiHoldingCertificate);

            $additionalRules = [
                'exchange_rate' => ['required', 'numeric'],
                'products' => ['required', 'array'],
                'products.*.product' => ['required', 'string'],
                'products.*.quantity' => ['required', 'numeric'],
                'products.*.amount' => ['required', 'numeric'],
                'products.*.currency' => ['required', 'string'],
                'products.*.warehouse' => ['required', 'string'],
                'products.*.owner' => ['required', 'string'],
                'products.*.previous_owner' => ['required', 'string'],
                'products.*.date_time_of_purchasing_commodity' => ['required', 'string', 'date_format:Y-m-d H:i:s'],
                'products.*.warehouse_or_vault_emirates' => ['required', 'string'],
                'products.*.warehouse_or_vault_country' => ['required', 'string'],
                'products.*.uom' => ['required', 'string'],
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
            ];
        }

        return array_merge($rules, $additionalRules);
    }
}
