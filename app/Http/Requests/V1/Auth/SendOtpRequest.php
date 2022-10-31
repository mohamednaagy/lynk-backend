<?php

namespace App\Http\Requests\V1\Auth;

use App\Rules\ValidateSAID;
use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return (int) $this->route('order')->national_id == (int) $this->national_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'national_id' => ['required', 'digits:10', new ValidateSAID],
        ];
    }
}
