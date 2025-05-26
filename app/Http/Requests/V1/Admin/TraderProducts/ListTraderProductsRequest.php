<?php

namespace App\Http\Requests\V1\Admin\TraderProducts;

use App\Enums\Trader;
use App\Enums\TraderProductStatus;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

class ListTraderProductsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
            'status' => ['nullable', 'string', new EnumValue(TraderProductStatus::class)],
            'provider' => ['nullable', 'string', new EnumValue(Trader::class)],
        ];
    }

    /**
     * Get the default values for the request.
     *
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'status' => TraderProductStatus::Enabled,
            'provider' => Trader::Bursam,
        ];
    }
}
