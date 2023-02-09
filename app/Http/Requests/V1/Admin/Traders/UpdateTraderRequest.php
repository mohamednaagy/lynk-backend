<?php

namespace App\Http\Requests\V1\Admin\Traders;

use App\Models\Company;
use App\Rules\CompanyUniqueNameRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTraderRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'min:3',
            ],
            'unique_name' => [
                'required',
                'string',
                'min:3',
                new CompanyUniqueNameRule,
                Rule::unique(Company::class, 'unique_name')
                    ->ignore($this->route('trader')),
            ],
            'driver' => [
                'nullable',
                'string',
                Rule::unique(Company::class, 'driver')
                    ->ignore($this->route('trader')),
                Rule::in(['dmcc', 'fake']),
            ],
            'notifications_email' => [
                'required',
                'email',
                'string',
                'max:255',
            ],
        ];
    }
}
