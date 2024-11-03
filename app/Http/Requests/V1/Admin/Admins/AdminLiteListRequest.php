<?php

namespace App\Http\Requests\V1\Admin\Admins;

use Illuminate\Foundation\Http\FormRequest;

class AdminLiteListRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Adjust authorization logic as needed
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
           'can_manage_orders' => ['required',  'boolean'],
        ];
    }
}
