<?php

namespace App\Http\Requests\V1\Admin\Lenders\Orders\TraderOrders;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSellingCommodityToClientRequest extends FormRequest
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
            'document' => ['exclude_if:automatically_generate_file,true', 'required', 'file', 'mimes:pdf'],
            'automatically_generate_file' => ['required', 'boolean'],
        ];
    }
}
