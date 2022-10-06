<?php

namespace Modules\Otpify\Http\Requests;

use App\Enums\Area;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OtpifyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = [
            'area' => ['required', Rule::in(Area::getValues())],
        ];

        if ($this->request->has('vid')) {
            $rules['vid'] = ['required', 'exists:otpify_codes,id'];
            $rules['code'] = ['required'];
        }

        return $rules;
    }
}
