<?php

namespace App\Http\Requests\Constant;

use Illuminate\Foundation\Http\FormRequest;

class ListConstantRequest extends FormRequest
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
            'constants' => 'required',
            'constants.*' => 'required|in:measurements,currencies',
        ];
    }

    public function prepareForValidation()
    {
        $constants = $this->input('constants');
        if (is_string($constants)) {
            $this->merge(['constants' => explode(',', $constants)]);
        }
    }
}
