<?php

namespace App\Http\Requests\V1\Admin\Transactions;

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
        // The regex will hold for quantities like '12' or '12.5' or '12.05'.
        //  If you want more decimal points than two,
        //  replace the "2" with the allowed decimals you need.

        return [
            'amount' => ['required', 'gt:0', 'regex:/^\d+(\.\d{1,2})?$/'],
        ];
    }
}
