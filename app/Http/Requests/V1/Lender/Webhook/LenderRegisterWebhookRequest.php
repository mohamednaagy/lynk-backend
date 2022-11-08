<?php

namespace App\Http\Requests\V1\Lender\Webhook;

use App\Enums\WebhookType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LenderRegisterWebhookRequest extends FormRequest
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
            'webhook_url' => ['required', 'url', 'max:265'],
            'webhook_type' => ['required', Rule::in(WebhookType::getValues())],
        ];
    }
}
