<?php

namespace App\Http\Requests\V1\Lender\Orders;


use Carbon\Carbon;
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
        return [
            'creation_start_date' => ['nullable', 'date', 'before_or_equal:'.Carbon::now()->toDateString(), $this->has('creation_end_date') ? 'before_or_equal:creation_end_date' : ''],
            'creation_end_date' => ['nullable', 'date', 'before_or_equal:'.Carbon::now()->toDateString(), $this->has('creation_start_date') ? 'after_or_equal:creation_start_date' : ''],
        ];
    }
}
