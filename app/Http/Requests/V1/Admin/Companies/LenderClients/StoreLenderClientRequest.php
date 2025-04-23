<?php

namespace App\Http\Requests\V1\Admin\Companies\LenderClients;

use App\Enums\CompanyLenderClientType;
use App\Models\CompanyLenderClient;
use App\Rules\AutoCompleteSell\NoOverlappingPeriods;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLenderClientRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:100'],
            'national_id' => ['required', 'integer', 'max_digits:10',
                Rule::unique(CompanyLenderClient::class, 'national_id')->where('company_id', $this->lender->id)->where('deleted_at', null), ],
            'type' => ['required', Rule::in(CompanyLenderClientType::getValues())],
            'auto_complete_sell' => ['required', 'boolean'],
            'auto_sell_periods' => ['required_if:auto_complete_sell,true', 'array', new NoOverlappingPeriods],
            'auto_sell_periods.*.effective_start' => ['required_if:auto_complete_sell,true', 'date_format:Y-m-d'],
            'auto_sell_periods.*.effective_end' => ['required_if:auto_complete_sell,true', 'date_format:Y-m-d', 'after_or_equal:auto_sell_periods.*.effective_start'],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => __('validation.field_is_required'),
            'national_id.required' => __('validation.field_is_required'),
            'type.required' => __('validation.field_is_required'),
            'name.max' => __('validation.max_string_chars'),
            'national_id.unique' => __('validation.unique_input'),
            'auto_complete_sell.required' => __('validation.field_is_required'),
            'auto_complete_sell.in' => __('validation.field_should_be_boolean'),
            'auto_sell_periods.required_if' => __('validation.field_is_required'),
            'auto_sell_periods.*.effective_start.required' => __('validation.field_is_required'),
            'auto_sell_periods.*.effective_end.required' => __('validation.field_is_required'),
            'auto_sell_periods.*.effective_start.date_format' => __('validation.invalid_date_format'),
            'auto_sell_periods.*.effective_end.date_format' => __('validation.invalid_date_format'),
            'auto_sell_periods.*.effective_end.after_or_equal' => __('validation.effective_end_after_or_equal_start'),
        ];
    }
}
