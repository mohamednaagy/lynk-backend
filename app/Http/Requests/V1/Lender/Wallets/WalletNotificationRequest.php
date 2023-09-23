<?php

namespace App\Http\Requests\V1\Lender\Wallets;

use App\Enums\WalletNotificationType;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

class WalletNotificationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => [
                'required',
                new EnumValue(WalletNotificationType::class),
            ],
            'value' => [
                'required',
                $this->input('type') === WalletNotificationType::ORDER_COUNT
                    ? 'integer'
                    : 'numeric',
            ],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
