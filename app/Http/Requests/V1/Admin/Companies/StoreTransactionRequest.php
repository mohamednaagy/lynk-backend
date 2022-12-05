<?php

namespace App\Http\Requests\V1\Admin\Companies;

use App\Rules\MoneyValueRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
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
            'amount' => ['required', 'gt:0', 'numeric', new MoneyValueRule],
            'description_en' => ['required', 'string', 'max:255'],
            'description_ar' => ['required', 'string', 'max:255'],
            'attachment' => ['required', 'file', 'mimes:png,jpg,jpeg,pdf', 'max:2048'],
        ];
    }
}
