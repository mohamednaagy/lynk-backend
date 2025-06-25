<?php

namespace App\Http\Requests\V1\Admin\Companies\LenderClients;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UploadAutoSellPeriodSupportingDocumentRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'media' => ['required', 'file', 'mimes:pdf', 'max:10240'], // Max 10MB
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $this->validateRelationships($validator);
        });
    }

    /**
     * Validate that the route parameters have proper relationships.
     */
    protected function validateRelationships(Validator $validator): void
    {
        $lender = $this->route('lender');
        $client = $this->route('client');
        $client_auto_sell_period = $this->route('client_auto_sell_period');

        // Check if client belongs to the lender
        if ($client && $lender && $client->company_id !== $lender->id) {
            $validator->errors()->add('client', __('validation.client_invalid_for_lender'));
        }

        // Check if auto sell period belongs to the client
        if ($client_auto_sell_period && $client && $client_auto_sell_period->company_lender_client_id !== $client->id) {
            $validator->errors()->add('client_auto_sell_period', __('validation.auto_sell_period_invalid_for_client'));
        }
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'media.required' => __('validation.supporting_document_required'),
            'media.file' => __('validation.supporting_document_must_be_file'),
            'media.mimes' => __('validation.supporting_document_must_be_pdf'),
            'media.max' => __('validation.supporting_document_max_size'),
        ];
    }
}
