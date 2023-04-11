<?php

namespace App\Http\Requests\V1\Admin\Lenders\Orders\TraderOrders;

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
            'trader' => ['required', 'string', Rule::in(['fake', 'dmcc', 'bursam'])],
            'reference_number' => ['required', 'string', 'max:100'],
            'version' => ['required'],
        ];
    }

    public function withValidator($validator)
    {
        $versions_arr = array_keys(config($this->trader.'-murabha-steps-versions'));
        $versions_arr[] = 'latest';

        $validator->after(function ($validator) use ($versions_arr) {
            if (! in_array($validator->safe()->version, $versions_arr)) {
                $validator->errors()->add('version', 'Invalid Trader Version.');
            }
        });
    }
}
