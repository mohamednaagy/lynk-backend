<?php

namespace App\Http\Requests\V1\Lender\Webhooks;

use App\Enums\WebhookType;
use App\Rules\WebhookTypeLimitRule;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

class StoreWebhookRequest extends FormRequest
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
            'url' => ['required', 'url', 'max:265'],
            'type' => ['required', new EnumValue(WebhookType::class), new WebhookTypeLimitRule(tenant())],
        ];
    }
}
