<?php

namespace App\Http\Requests\V1\Lender\Wallets;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class CalculateOrdersRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'orders_count' => ['required', 'integer', 'min:1'],
        ];
    }
}
