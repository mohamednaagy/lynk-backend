<?php

namespace App\Http\Requests\V1\Admin\FinancingOrders;

use App\Enums\FinancingOrderStatus;
use BenSampo\Enum\Rules\EnumValue;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListOrderRequest extends FormRequest
{
    //    const FILTER_ACTIVE = 'active';
    //    const FILTER_NEED_ACTION = 'need_action';
    //    const FILTER_COMPLETED = 'completed';
    //    const FILTER_CANCELLED = 'cancelled';
    //    const FILTER_REJECTED = 'rejected';
    //
    //    const FILTERS = [
    //        self::FILTER_ACTIVE,
    //        self::FILTER_NEED_ACTION,
    //        self::FILTER_COMPLETED,
    //        self::FILTER_CANCELLED,
    //        self::FILTER_REJECTED,
    //    ];

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
            //                'sort' => ['nullable', Rule::in('created_at', 'amount')],
            //                'direction' => ['nullable', Rule::in('asc', 'desc')],
            //                'search' => ['nullable' , 'string'],
            //                'status' => ['nullable', 'array'],
            //                'status.*' => ['nullable', new EnumValue(FinancingOrderStatus::class, false)],
            //                'amount_lte' => ['nullable', 'numeric', isset($data['amount_gte']) ? 'gte:amount_gte' : ''],
            //                'amount_gte' => ['nullable', 'numeric', isset($data['amount_lte']) ? 'lte:amount_lte' : ''],
            //                'filter' => ['nullable', Rule::in(self::FILTERS)],
            //                'company' => ['nullable', 'array'],
            //                'company.*' => ['nullable', 'exists:companies,id'],
            'creation_start_date' => ['nullable', 'date', 'before_or_equal:creation_end_date', 'before_or_equal:'.Carbon::now()->toDateString()],
            'creation_end_date' => ['nullable', 'date', 'after_or_equal:creation_start_date', 'before_or_equal:'.Carbon::now()->toDateString()],

        ];
    }
}
