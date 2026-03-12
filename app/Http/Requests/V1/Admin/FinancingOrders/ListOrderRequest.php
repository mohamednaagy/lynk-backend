<?php

namespace App\Http\Requests\V1\Admin\FinancingOrders;

use Illuminate\Foundation\Http\FormRequest;

class ListOrderRequest extends FormRequest
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
        $saudiNow = saudi_now('Y-m-d');

        return [
            'creation_start_date' => ['nullable', 'date', 'before_or_equal:'.$saudiNow, $this->has('creation_end_date') ? 'before_or_equal:creation_end_date' : ''],
            'creation_end_date' => ['nullable', 'date', 'before_or_equal:'.$saudiNow, $this->has('creation_start_date') ? 'after_or_equal:creation_start_date' : ''],
        ];
    }
}
