<?php

namespace App\Http\Requests\V1\Admin\Companies;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class ListTransactionRequest extends FormRequest
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
            'date_from' => ['nullable', 'date',  'before_or_equal:'.Carbon::now()->toDateString(), $this->has('date_to') ? 'before_or_equal:date_to' : ''],
            'date_to' => ['nullable', 'date', 'before_or_equal:'.Carbon::now()->toDateString(),  $this->has('date_from') ? 'after_or_equal:date_from' : ''],
            'amount_lte' => ['nullable', 'different:amount_gte', 'numeric', $this->has('amount_gte') ? 'gte:amount_gte' : ''],
            'amount_gte' => ['nullable',  'numeric', $this->has('amount_lte') ? 'lte:amount_lte' : ''],
            'page' => ['nullable', 'numeric'],
        ];
    }
}
