<?php

namespace App\Http\Requests\V1\Lender\Wallets;

use App\Enums\WalletNotificationType;
use App\Models\Lender;
use BenSampo\Enum\Rules\EnumValue;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class WalletNotificationRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Lender $lender */
        $lender = tenant();

        return [
            'type' => [
                'exclude_if:value,null',
                'required',
                $lender->isTiered()
                    ? function (string $attribute, mixed $value, Closure $fail) {
                        if ($value !== WalletNotificationType::WALLET_BALANCE) {
                            $fail($attribute, trans('validation.not_in', ['attribute' => $attribute]));
                        }
                    }
                    : new EnumValue(WalletNotificationType::class),
            ],
            'value' => [
                'nullable',
                'gte:0',
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
