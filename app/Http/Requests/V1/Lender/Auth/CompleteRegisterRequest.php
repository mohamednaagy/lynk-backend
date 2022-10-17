<?php
namespace App\Http\Requests\V1\Lender\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class CompleteRegisterRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:250'],
            'last_name' => ['required', 'string', 'max:250'],
            'password' => ['required', 'string', 'confirmed'],
        ];
    }
}
