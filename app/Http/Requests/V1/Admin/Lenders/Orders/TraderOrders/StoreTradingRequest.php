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
            'commodity_type_id' => [
                'nullable',
                'numeric',
                function ($attribute, $value, $fail) {
                    // Allow -1 as a special value for "any selection"
                    if ($value == -1) {
                        return;
                    }

                    // For other values, use the existing validation rule
                    $rule = new CheckCommodityTypeActiveRule($this->trader);
                    if (! $rule->passes($attribute, $value)) {
                        $fail($rule->message());
                    }
                },
            ],
        ];
    }

    /**
     * It transforms the commodity_type_id field based on mode and user selection:
     * - When mode is Manual and commodity_type_id is not explicitly set to -1: sets to null (commodity selection not required)
     * - When commodity_type_id is -1: preserves -1 (user selected "any") regardless of mode
     * - Otherwise: keeps the original value
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        if ($this->input('mode') == TraderOrderMode::Manual && $this->input('commodity_type_id') != -1) {
            // For manual mode, set to null only if user didn't explicitly choose "any" (-1)
            $this->merge([
                'commodity_type_id' => null,
            ]);
        }
        // For all other cases (including when commodity_type_id is -1), preserve the original value
    }
}
