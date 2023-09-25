<?php

namespace App\Http\Requests\V1\Lender\Wallets;

use App\Enums\WalletNotificationType;
use App\Models\Company;
use BenSampo\Enum\Rules\EnumValue;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class WalletNotificationRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Company $company */
        $company = tenant();

        return [
            'type' => [
                'required',
                $company->isTiered()
                    ? function (string $attribute, mixed $value, Closure $fail) {
                        if ($value !== WalletNotificationType::WALLET_BALANCE) {
                            $fail(trans('validation.not_in', ['attribute' => $attribute]));
                        }
                    }
                    : new EnumValue(WalletNotificationType::class),
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
