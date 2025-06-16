<?php

namespace App\Http\Requests\V1\Admin\Lenders\Orders\TraderOrders;

use App\Enums\TraderOrderMode;
use App\Rules\CheckCommodityTypeActiveRule;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTradingRequest extends FormRequest
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
            'trader' => ['required', 'string', Rule::in(['fake', 'dmcc', 'bursam', 'lynk'])],
            'reference_number' => ['nullable', 'required_if:mode,'.TraderOrderMode::Manual, 'string', 'max:100'],
            'mode' => ['required', 'string', new EnumValue(TraderOrderMode::class)],
            'commodity_type_id' => ['nullable', 'numeric', new CheckCommodityTypeActiveRule($this->trader)],
        ];
    }

    /**
     * It transforms the commodity_type_id field to null in the following cases:
     * - When the commodity_type_id is explicitly set to -1 (used to represent 'any selection' in UI).
     * - When the mode is set to Manual, meaning commodity selection is not required.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        if ($this->input('commodity_type_id') == -1 || $this->input('mode') == TraderOrderMode::Manual) {
            $this->merge([
                'commodity_type_id' => null,
            ]);
        }
    }
}
