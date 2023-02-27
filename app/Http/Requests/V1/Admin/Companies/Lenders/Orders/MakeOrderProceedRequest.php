<?php

namespace App\Http\Requests\V1\Admin\Companies\Lenders\Orders;

use App\Enums\FinancingOrderProceedCase;
use App\Http\Requests\Traits\RequestHasClientWakala;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

class MakeOrderProceedRequest extends FormRequest
{
    use RequestHasClientWakala;

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
            'case' => ['required', 'string', new EnumValue(FinancingOrderProceedCase::class)],
            'client_wakala' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg'],
        ];
    }
}
