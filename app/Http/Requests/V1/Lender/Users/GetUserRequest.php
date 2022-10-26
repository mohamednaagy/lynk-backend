<?php

namespace App\Http\Requests\V1\Lender\Users;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetUserRequest extends FormRequest
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
        return [
            'user' => ['required', Rule::exists(User::class, 'id')],
        ];
    }

    public function all($keys = null): ?array
    {
        return array_merge(parent::all(), $this->route()->parameters());
    }
}
